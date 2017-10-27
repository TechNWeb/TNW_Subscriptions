<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary\Payment;

use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Block\Subscription\Summary\BaseSummary;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Class for subscription profile summary payment details block on frontend Customer Account.
 */
class Details extends BaseSummary
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
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * @var string
     */
    private $requestProfileIdField;

    /**
     * @var SubscriptionProfileInterface
     */
    private $subscriptionProfile;

    /**
     * Details constructor.
     * @param Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        ProfileManager $profileManager,
        $requestProfileIdField = SummaryInsertForm::FORM_DATA_KEY,
        array $data = []
    ) {
        $this->profileManager = $profileManager;
        $this->paymentConfig = $paymentConfig;
        $this->requestProfileIdField = $requestProfileIdField;
        $this->setTemplate('TNW_Subscriptions::subscription_profile/summary/overview/payment-details.phtml');
        parent::__construct($context);
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
        $creditCardExpDate = '';
        if (isset($additionalInfo['cc_exp_month']) && isset($additionalInfo['cc_exp_year'])) {
            $creditCardExpDate = "{$additionalInfo['cc_exp_month']}/{$additionalInfo['cc_exp_year']}";
        }

        return $creditCardExpDate;
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

    /**
     * Return if is necessary to show block title
     *
     * @return null|bool
     */
    public function isShowTitle()
    {
        return $this->getShowTitle();
    }

    /**
     * Return if only table would be shown
     * @return null|bool
     */
    public function isOnlyTableContent()
    {
        return $this->getOnlyTableContent();
    }

    /**
     * @inheritdoc
     */
    public function getEditUrl($tabName = 'billing', array $params = [])
    {
        $params['payment_details'] = 1;

        return parent::getEditUrl($tabName, $params);
    }

    /**
     * Return subscription profile model
     *
     * @return SubscriptionProfileInterface
     */
    public function getSubscriptionProfile()
    {
        if (!$this->subscriptionProfile) {
            $this->subscriptionProfile = $this->profileManager->loadProfileFromRequest($this->requestProfileIdField);
        }

        return $this->subscriptionProfile;
    }
}
