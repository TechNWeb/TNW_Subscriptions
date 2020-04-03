<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use CyberSource\Core\Model\Config as ConfigProvider;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\Exception\PaymentException;

/**
 * Class CyberSource
 * @package TNW\Subscriptions\Model\SubscriptionProfile\Engine
 */
class CyberSource extends Base
{
    /**
     * @var mixed
     */
    private $transferFactory;

    /**
     * @var \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer
     */
    private $transactionCustomer;

    /**
     * @var mixed
     */
    private $requestHelper;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * CyberSource constructor.
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param \TNW\Subscriptions\Model\Config $config
     * @param \TNW\Subscriptions\Model\Context $context
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Framework\App\Request\DataPersistorInterface $persistor
     * @param \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer
     * @param \TNW\Subscriptions\Plugin\CyberSource\SecureAcceptance\Gateway\Config\Config $cyberSourceConfig
     */
    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        \TNW\Subscriptions\Model\Config $config,
        \TNW\Subscriptions\Model\Context $context,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\App\Request\DataPersistorInterface $persistor,
        \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer,
        \TNW\Subscriptions\Plugin\CyberSource\SecureAcceptance\Gateway\Config\Config $cyberSourceConfig
    ) {
        parent::__construct(
            $config,
            $context,
            $cartManagement,
            $persistor,
            $zeroTotalValidator
        );
        $cyberSourceConfig->reBillProcess();
        if (
            $moduleManager->isEnabled("CyberSource_Core")
            && $moduleManager->isEnabled("CyberSource_SecureAcceptance")
        ) {
            $this->transferFactory = $objectManager->get("CyberSource\Core\Gateway\Http\TransferFactory");
            $this->requestHelper = $objectManager->get("CyberSource\SecureAcceptance\Helper\RequestDataBuilder");
        }
        $this->transactionCustomer = $transactionCustomer;
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        $cybersourceToken = $payment->getAdditionalInformation('cybersourse_token');
        $cyberSourceData = $payment->getAdditionalInformation();
        return [
            'token_hash' => $cybersourceToken,
            'encoded_payment_additional_info' => [
                OrderPaymentInterface::CC_TYPE => $payment->getCcType(),
                OrderPaymentInterface::CC_LAST_4 => $payment->getCcLast4(),
                OrderPaymentInterface::CC_EXP_MONTH => $payment->getCcExpMonth(),
                OrderPaymentInterface::CC_EXP_YEAR => $payment->getCcExpYear(),
                'cybersource_data' =>$cyberSourceData
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        $result = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
            : [];

        $result[OrderPaymentInterface::METHOD] = ConfigProvider::CODE;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile)
    {
        $profilePayment = $profile->getPayment();
        $result = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
            : [];
        $cyberSourceData = isset($result['cybersource_data']) ? $result['cybersource_data'] : [];
        if (!isset($result['cybersource_data'])) {
            $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
                $profilePayment->getPaymentToken(),
                'cybersource',
                $profile->getCustomerId()
            );
            $cyberSourceData = [
                'cybersource_token' => $profilePayment->getPaymentToken(),
                'cardNumber' => str_replace('-', '', $result['cc_last_4']),
                'cardType' => $this->requestHelper->getCardType($result['cc_type']),
                "is_active_payment_token_enabler" => true,
                'token_data' => [
                    "payment_token" => $profilePayment->getPaymentToken(),
                    "card_type" => $this->requestHelper->getCardType($result['cc_type']),
                    "cc_last4" => $result['cc_last_4'],
                    "card_expiry_date" => $result['cc_exp_month'] . '-' . $result['cc_exp_year']
                ]
            ];
            if ($paymentToken) {
                $cyberSourceData['extension_attributes'] = json_decode($paymentToken->getTokenDetails(), true);
            }
        }
        return array_merge(
            $cyberSourceData,
            [OrderPaymentInterface::METHOD => ConfigProvider::CODE]
        );
    }

    /**
     * @param $requestData
     * @return $this|Base
     * @throws PaymentException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function processProfileByRequestData($requestData)
    {
        if (empty($requestData['payment'][ConfigProvider::CODE]['method'])) {
            return $this;
        }

        $customer = $this->getProfile()->getCustomer();
        if (!$customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            return $this;
        }

        /** @var string[] $additionalData */
        $additionalData = $requestData['payment'][ConfigProvider::CODE]['additional'];

        $transfer = $this->transferFactory->create([
            'firstName' => $customer->getFirstname(),
            'lastName' => $customer->getLastname(),
            'email' => $customer->getEmail(),
            'paymentMethodNonce' => $requestData['payment'][ConfigProvider::CODE]['nonce']
        ]);

        $response = $this->transactionCustomer->placeRequest($transfer);
        if ($response['object'] instanceof \Braintree\Result\Error) {
            $errors = [];
            foreach($response->errors->deepAll() AS $error) {
                $errors[] = "{$error->code}: {$error->message}";
            }

            throw new PaymentException(__('CyberSource message: %1', implode(', ', $errors)));
        }

        /** @var \Braintree\CreditCard $paymentMethod */
        $paymentMethod = $response['object']->customer->paymentMethods[0];

        $this->getProfile()->getPayment()
            ->setPaymentToken($paymentMethod->token)
            ->setEncodedPaymentAdditionalInfo([
                OrderPaymentInterface::CC_TYPE => $additionalData['cc_type'],
                OrderPaymentInterface::CC_LAST_4 => $paymentMethod->last4,
                OrderPaymentInterface::CC_EXP_MONTH => $paymentMethod->expirationMonth,
                OrderPaymentInterface::CC_EXP_YEAR => $paymentMethod->expirationYear,
            ]);

        return $this;
    }
}
