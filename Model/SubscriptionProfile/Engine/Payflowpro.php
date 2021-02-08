<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Config;

/**
 * Class Payflowpro - used as processor for payflowpro payments
 */
class Payflowpro extends Base
{
    /**
     * Token value field
     */
    const PNREF = 'pnref';

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        $paymentToken = $payment->getAdditionalInformation(self::PNREF);
        if (!$paymentToken && $payment->getAdditionalInformation('public_hash')) {
            $token = $this->paymentTokenManagement->getByPublicHash(
                $payment->getAdditionalInformation('public_hash'),
                $payment->getQuote()->getCustomerId()
            );
            if ($token) {
                $paymentToken = $token->getGatewayToken();
            }
        }
        $result = parent::getProfilePaymentInfo($payment);
        $result['payment_token'] = $paymentToken;
        $result['encoded_payment_additional_info'] = [
            OrderPaymentInterface::CC_TYPE => $payment->getCcType(),
            OrderPaymentInterface::CC_LAST_4 => $payment->getCcLast4(),
            OrderPaymentInterface::CC_EXP_MONTH => $payment->getCcExpMonth(),
            OrderPaymentInterface::CC_EXP_YEAR => $payment->getCcExpYear()
        ];
        return $result;
    }

    /**
     * @return string
     */
    public function getVaultPaymentCode()
    {
        return 'payflowpro_cc_vault';
    }

    /**
     * @param $requestData
     * @return $this|Base
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function processProfileByRequestDataVault($requestData)
    {
        if (empty($requestData['payment'][$this->getVaultPaymentCode()]['method'])) {
            return $this;
        }
        $customer = $this->getProfile()->getCustomer();
        if (!$customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            return $this;
        }

        $paymentToken = $this->paymentTokenManagement->getByPublicHash(
            $requestData['payment'][$this->getVaultPaymentCode()]['additional']['publicHash'],
            $customer->getId()
        );
        /** @var string[] $additionalData */
        $additionalData = $requestData['payment'][$this->getVaultPaymentCode()]['additional'];
        $additionalData['public_hash'] = $additionalData['publicHash'];
        $paymentData = $requestData['payment'][$this->getVaultPaymentCode()];
        $paymentData['method'] = $this->getVaultPaymentCode();
        $paymentData['additional_data'] = array_merge($paymentData, $additionalData);
        $tempQuote = $this->manager->getTempQuote($this->getProfile());
        $tempQuote->getPayment()->setMethod($this->getPaymentMethodCode());
        $tempQuote->getPayment()->setQuote($tempQuote);
        $this->vaultPaymentAuthorization->processPreAuthForTrial(
            $paymentData,
            $tempQuote
        );
        $this->populateProfilePayment($paymentToken);
        return $this;
    }

    /**
     * @param $requestData
     * @return Base|EngineInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function processProfileByRequestData($requestData)
    {
        if (!empty($requestData['payment'][$this->getPaymentMethodCode()]['method'])) {
            $editData = $this->persistor->get(
                \TNW\Subscriptions\Model\SubscriptionProfile\Engine\EngineInterface::PAYMENT_DATA_KEY
            );
            $requestData['payment'][$this->getPaymentMethodCode()]['additional_information'][self::PNREF]
                = isset($editData['pnref'])
                ? $editData['pnref']
                : '';
        }
        return parent::processProfileByRequestData($requestData);
    }

    /**
     * @param $paymentToken
     * @return $this|Base
     */
    protected function populateProfilePayment($paymentToken)
    {
        $tokenDetails = json_decode($paymentToken->getTokenDetails(), true);
        $this->getProfile()->getPayment()
            ->setEngineCode($this->getVaultPaymentCode())
            ->setPaymentToken($paymentToken->getGatewayToken())
            ->setEncodedPaymentAdditionalInfo([
                OrderPaymentInterface::CC_TYPE => $tokenDetails['cc_type'],
                OrderPaymentInterface::CC_LAST_4 => $tokenDetails['cc_last_4'],
                OrderPaymentInterface::CC_EXP_MONTH => $tokenDetails['cc_exp_month'],
                OrderPaymentInterface::CC_EXP_YEAR => $tokenDetails['cc_exp_year'],
            ]);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        $result = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
            : [];

        $result[OrderPaymentInterface::METHOD] = Config::METHOD_PAYFLOWPRO;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile)
    {
        return [
            self::PNREF => $profile->getPayment()->getPaymentToken()
        ];
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validatePayment(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getBaseGrandTotal() < 0.0001) {
            /** @var Payment $payment */
            $payment = $quote->getPayment();
            $payment->unsetData('method_instance');
            $quote->setSubscriptionPaymentDataSet(true);
            $payment->importData(['method' => \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE]);
            $payment->setAdditionalInformation([]);
        } else {
            parent::validatePayment($quote);
        }
    }

    /**
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return Config::METHOD_PAYFLOWPRO;
    }
}
