<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Summary;

use Magento\Backend\Block\Template;
use Magento\Payment\Model\Config;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Class PaymentDetails
 */
class PaymentDetails extends Template
{
    /**
     * Subscription profile
     *
     * @var SubscriptionProfileInterface
     */
    private $subscriptionProfile;

    /**
     * Subscription profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * Payment additional info
     *
     * @var array
     */
    private $additionalInfo;

    /**
     * Payment config
     *
     * @var Config
     */
    private $paymentConfig;

    /**
     * ShippingDetails constructor.
     * @param ProfileManager $profileManager
     * @param Config $paymentConfig
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        ProfileManager $profileManager,
        Config $paymentConfig,
        Template\Context $context,
        array $data = []

    ) {
        $this->profileManager = $profileManager;
        $this->paymentConfig = $paymentConfig;
        $this->setTemplate('TNW_Subscriptions::subscription_profile/summary/payment_details.phtml');
        parent::__construct($context, $data);
    }

    /**
     * Return subscription payment description
     *
     * @return null|string
     */
    public function getPaymentMethodTitle()
    {
        $path = 'payment/' . $this->getProfile()->getEngineCode() . '/title';
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
            $this->additionalInfo = $this->getProfile()->getPaymentAdditionalInfo();
            if ($this->additionalInfo) {
                $this->additionalInfo = (array)json_decode($this->additionalInfo);
            }
        }
        return $this->additionalInfo;
    }

    /**
     * Returns subscription profile from registry
     *
     * @return mixed|SubscriptionProfileInterface
     */
    private function getProfile()
    {
        if (null === $this->subscriptionProfile) {
            /** @var SubscriptionProfile $model */
            $this->subscriptionProfile =
                $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        }
        return $this->subscriptionProfile;
    }
}