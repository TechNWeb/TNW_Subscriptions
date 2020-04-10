<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\Framework\Exception\PaymentException;

/**
 * Authorizenet Engine
 */
class Authorizenet extends Base
{
    /**
     * @var \Magento\Braintree\Gateway\Http\TransferFactory
     */
    private $transferFactory;

    /**
     * @var \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer
     */
    private $transactionCustomer;

    private $isRebill = false;

    private $paymentTokenManagement;

    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        \TNW\Subscriptions\Model\Context $context,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\App\Request\DataPersistorInterface $persistor,
        \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer,
        \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement
    ) {
        parent::__construct(
            $config,
            $context,
            $cartManagement,
            $persistor,
            $zeroTotalValidator
        );
        $this->paymentTokenManagement = $paymentTokenManagement;
        if ($moduleManager->isEnabled("TNW_AuthorizeCim")) {
            $this->transferFactory = $objectManager->get("TNW\AuthorizeCim\Gateway\Http\TransferFactory");
        }
        $this->transactionCustomer = $transactionCustomer;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        $additionalInfo = $payment->getAdditionalInformation();
        if (array_key_exists('extension_attributes', $additionalInfo)) {
            $cardDetails = json_decode($additionalInfo['extension_attributes'], true);
            $expirateionDate = explode('/' , $cardDetails['expirationDate']);
        }
        $result = [
            'encoded_payment_additional_info' => [
                OrderPaymentInterface::CC_TYPE => isset($additionalInfo[OrderPaymentInterface::CC_TYPE])
                    ? $additionalInfo[OrderPaymentInterface::CC_TYPE]
                    : $cardDetails['type'],
                OrderPaymentInterface::CC_LAST_4 => $payment->getCcLast4(),
                OrderPaymentInterface::CC_EXP_MONTH => isset($additionalInfo[OrderPaymentInterface::CC_EXP_MONTH])
                    ? $additionalInfo[OrderPaymentInterface::CC_EXP_MONTH]
                    : $expirateionDate[0],
                OrderPaymentInterface::CC_EXP_YEAR => isset($additionalInfo[OrderPaymentInterface::CC_EXP_YEAR])
                    ? $additionalInfo[OrderPaymentInterface::CC_EXP_YEAR]
                    : $expirateionDate[1],
                'authorizenet_data' => $additionalInfo
            ]
        ];
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        $result = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
            : [];

        $result[OrderPaymentInterface::METHOD] = $this->getPaymentMethodCode();
        return $result;
    }

    public function setPaymentExtensionAttributes(Payment $payment, SubscriptionProfileInterface $profile)
    {
        $token = $this->paymentTokenManagement->getByGatewayToken(
            $profile->getPayment()->getPaymentToken(),
            $this->getPaymentMethodCode(),
            $profile->getCustomerId()
        );
        if ($token) {
            $payment->setData('public_hash', $token->getPublicHash());
        }
        $payment->setData('method', $this->getPaymentMethodCode() . '_vault');
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile)
    {
        $addtionalInfo = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
            : [];
        $gateWayToken = $this->paymentTokenManagement->getByGatewayToken(
            $profile->getPayment()->getPaymentToken(),
            $this->getPaymentMethodCode(),
            $profile->getCustomerId()
        );
        if (!$gateWayToken) {
            $gateWayToken = $this->paymentTokenManagement->getByGatewayToken(
                $profile->getPayment()->getPaymentToken(),
                $this->getPaymentMethodCode(),
                0
            );
        }
        $publicHash = $gateWayToken ? $gateWayToken->getPublicHash() : '';
        $result = isset($addtionalInfo['authorizenet_data']) ? $addtionalInfo['authorizenet_data'] : $addtionalInfo;
        if ($this->isRebill) {
            $result = [
                'is_active_payment_token_enabler' => true,
                'public_hash' => $publicHash,
                'customer_id' => $profile->getCustomerId()
            ];
        }
        return $result;
    }

    public function getPaymentMethodCode()
    {
        //TODO: resolve if vault method
        return 'tnw_authorize_cim';
    }

     public function setRebillProcessFlag()
     {
         $this->isRebill = true;
     }

    /**
     * @inheritdoc
     * @param $requestData
     * @return Braintree
     * @throws PaymentException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function processProfileByRequestData($requestData)
    {
        if (empty($requestData['payment'][$this->getPaymentMethodCode()]['method'])) {
            return $this;
        }

        $customer = $this->getProfile()->getCustomer();
        if (!$customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            return $this;
        }

        /** @var string[] $additionalData */
        $additionalData = $requestData['payment'][$this->getPaymentMethodCode()]['additional'];

        $transfer = $this->transferFactory->create([
            'firstName' => $customer->getFirstname(),
            'lastName' => $customer->getLastname(),
            'email' => $customer->getEmail(),
            'paymentMethodNonce' => $requestData['payment'][$this->getPaymentMethodCode()]['nonce']
        ]);

        $response = $this->transactionCustomer->placeRequest($transfer);
        if ($response['object'] instanceof \Braintree\Result\Error) {
            $errors = [];
            foreach($response->errors->deepAll() AS $error) {
                $errors[] = "{$error->code}: {$error->message}";
            }

            throw new PaymentException(__('Braintree message: %1', implode(', ', $errors)));
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
