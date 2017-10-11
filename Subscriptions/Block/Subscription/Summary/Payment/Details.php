<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary\Payment;

use Magento\Framework\View\Element\Template;

/**
 * Class Payment Details
 *
 * @method \TNW\Subscriptions\Model\SubscriptionProfile getSubscriptionProfile()
 */
class Details extends Template
{

    /**
     * @var array
     */
    private $additionalInfo;

    /**
     * @var \Magento\Payment\Model\Config
     */
    private $paymentConfig;

    /**
     * Details constructor.
     * @param Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        array $data = [])
    {
        parent::__construct($context, $data);
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * Return subscription payment description
     *
     * @return null|string
     */
    public function getPaymentMethodTitle()
    {
        $path = 'payment/' . $this->getSubscriptionProfile()->getEngineCode() . '/title';
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->getStore());
    }

    /**
     * Returns credit card number
     * @return string
     */
    public function getCreditCardNumber()
    {
        $additionalInfo = $this->getPaymentAdditionalInfo();
        $creditCardNumber = '';
        if (isset($additionalInfo['cc_last_4'])) {
            $creditCardNumber = sprintf('XXXX%s', $additionalInfo['cc_last_4']);
        }

        return $creditCardNumber;
    }

    /**
     * Returns credit card exp. date
     * @return string
     */
    public function getCreditCardExpDate()
    {
        $additionalInfo = $this->getPaymentAdditionalInfo();
        $creditCardNumber = '';
        if (isset($additionalInfo['cc_exp_month']) && isset($additionalInfo['cc_exp_year'])) {
            $creditCardNumber = "{$additionalInfo['cc_exp_month']}/{$additionalInfo['cc_exp_year']}";
        }

        return $creditCardNumber;
    }

    /**
     * Returns credit card type label
     * @return string
     */
    public function getCreditCardTypeLabel()
    {
        $additionalInfo = $this->getPaymentAdditionalInfo();
        $creditCardTypeLabel = __('Unknown Cart Type');
        if (isset($additionalInfo['cc_type'])) {
            $ccTypes = $this->paymentConfig->getCcTypes();
            foreach ($ccTypes as $key => $label) {
                if ($key == $additionalInfo['cc_type']) {
                    $creditCardTypeLabel = $label;
                }
            }
        }

        return $creditCardTypeLabel;
    }

    /**
     * Retrieves payment additional info
     * @return null|array
     */
    public function getPaymentAdditionalInfo()
    {
        if (null === $this->additionalInfo) {
            $this->additionalInfo = $this->getSubscriptionProfile()->getPaymentAdditionalInfo();
            if ($this->additionalInfo) {
                $this->additionalInfo = (array)json_decode($this->additionalInfo);
            }
        }

        return $this->additionalInfo;
    }
}