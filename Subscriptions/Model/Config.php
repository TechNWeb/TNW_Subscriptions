<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\Data\StoreInterface;
use TNW\Subscriptions\Block\Adminhtml\System\Config\PaymentMethods\ActiveMethods;
use Magento\Paypal\Model\Config as PaypalConfig;

/**
 * Class Config
 */
class Config
{
    /**#@+
     * Config xml path for General section
     */
    private $xmlIsActive = 'tnw_subscriptions_general/general/active';
    private $xmlPurchaseType = 'tnw_subscriptions_product/general/purchase_type';
    private $xmlStartDateType = 'tnw_subscriptions_product/general/start_date_type';
    private $xmlLockProductPriceStatus = 'tnw_subscriptions_product/general/lock_product_price_status';
    private $xmlUnlockPresetQty = 'tnw_subscriptions_product/general/unlock_preset_qty_status';
    /**#@-*/

    /**#@+
     * Config xml path for past due profile options section
     */
    private $xmlAttemptCount = 'tnw_subscriptions_profile_options/past_due_profile_options/attempt_count';
    private $xmlAttemptInterval = 'tnw_subscriptions_profile_options/past_due_profile_options/attempt_interval';
    private $xmlGracePeriod = 'tnw_subscriptions_profile_options/past_due_profile_options/grace_period';
    /**#@-*/

    /**#@+
     * Config xml path for past advanced section
     */
    private $xmlGeneratedQuotesCount = 'tnw_subscriptions_general/advanced/generated_quotes_count';
    /**#@-*/

    /**#@+
     * Config xml path for Trial section
     */
    private $xmlTrialStatus = 'tnw_subscriptions_product/trial/trial_status';
    private $xmlTrialLength = 'tnw_subscriptions_product/trial/trial_length';
    private $xmlTrialLengthUnit = 'tnw_subscriptions_product/trial/trial_length_unit';
    private $xmlTrialPrice = 'tnw_subscriptions_product/trial/trial_price';
    private $xmlTrialStartDateType = 'tnw_subscriptions_product/trial/trial_start_date_type';
    /**#@-*/

    /**#@+
     * Config xml path for Discount section
     */
    private $xmlOfferFlatDiscountStatus = 'tnw_subscriptions_product/discount/offer_flat_discount_status';
    private $xmlDiscountAmount = 'tnw_subscriptions_product/discount/discount_amount';
    private $xmlDiscountType = 'tnw_subscriptions_product/discount/discount_type';
    /**#@-*/

    private $xmlUntilCanceled = 'tnw_subscriptions_product/term/until_canceled';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var PaypalConfig
     */
    private $paypalConfig;

    /**
     * @var bool
     */
    private $isSubscriptionsActive;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Http $request
     * @param PaypalConfig $paypalConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Http $request,
        PaypalConfig $paypalConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->paypalConfig = $paypalConfig;
    }

    /**
     * Get "Enable Subscriptions" config value for website or for all websites.
     *
     * @param int|null $websiteId
     * @return bool|mixed|null|string
     */
    public function isSubscriptionsActive($websiteId = null)
    {
        $result = false;

        if ($websiteId){
            $result = $this->getStoreConfig($this->xmlIsActive, $websiteId) && !empty($this->getAvailablePaymentsList($websiteId));
        } else {
            if ($this->isSubscriptionsActive === null) {
                $this->isSubscriptionsActive = false;
                foreach ($this->storeManager->getWebsites() as $website) {
                    if ($this->getStoreConfig($this->xmlIsActive, $website->getId())
                    && !empty($this->getAvailablePaymentsList($websiteId))) {
                        $this->isIntegrationActive = true;
                        $result = true;
                    }
                }
            } else {
                return $this->isSubscriptionsActive;
            }
        }

        return $result;
    }

    /**
     * Get "Enable Subscriptions" config value for current website
     *
     * @return bool
     */
    public function isSubscriptionsActiveCurrent()
    {
        $currentWebsiteId = $this->storeManager->getWebsite()->getId();
        return $this->getStoreConfig($this->xmlIsActive, $currentWebsiteId)
            && !empty($this->getAvailablePaymentsList($currentWebsiteId));
    }

    /**
     * Get "Purchase type" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getPurchaseType($websiteId = null)
    {
        return  $this->getStoreConfig($this->xmlPurchaseType, $websiteId);
    }

    /**
     * Get "Attempt count" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getAttemptCount($websiteId = null)
    {
        return  $this->getStoreConfig($this->xmlAttemptCount, $websiteId);
    }

    /**
     * Get "Attempt interval" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getAttemptInterval($websiteId = null)
    {
        return  $this->getStoreConfig($this->xmlAttemptInterval, $websiteId);
    }

    /**
     * Get "Grace period" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getGracePeriod($websiteId = null)
    {
        return  $this->getStoreConfig($this->xmlGracePeriod, $websiteId);
    }

    /**
     * Get "Generated quotes count" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getGeneratedQuotesCount($websiteId = null)
    {
        return  $this->getStoreConfig($this->xmlGeneratedQuotesCount, $websiteId);
    }

    /**
     * Get "Start date type" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getStartDateType($websiteId = null)
    {
        return $this->getStoreConfig($this->xmlStartDateType, $websiteId);
    }

    /**
     * Get "Lock product price status" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return bool
     */
    public function getLockProductPriceStatus($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlLockProductPriceStatus, $websiteId);
        return $value ? true : false;
    }

    /**
     * Get "Unlock Preset Qty" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return bool
     */
    public function getUnlockPresetQtyStatus($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlUnlockPresetQty, $websiteId);
        return $value ? true : false;
    }

    /**
     * Get "Offer flat discount status" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return bool
     */
    public function getOfferFlatDiscountStatus($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlOfferFlatDiscountStatus, $websiteId);
        return $value ? true : false;
    }

    /**
     * Get "Discount amount" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getDiscountAmount($websiteId = null)
    {
        return $this->getStoreConfig($this->xmlDiscountAmount, $websiteId);
    }

    /**
     * Get "Discount type" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getDiscountType($websiteId = null)
    {
        return $this->getStoreConfig($this->xmlDiscountType, $websiteId);
    }

    /**
     * Get "Trial status" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return bool
     */
    public function getTrialStatus($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlTrialStatus, $websiteId);
        return $value ? true : false;
    }

    /**
     * Get "Trial length" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getTrialLength($websiteId = null)
    {
        return $this->getStoreConfig($this->xmlTrialLength, $websiteId);
    }

    /**
     * Get "Trial length unit" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getTrialLengthUnit($websiteId = null)
    {
        return $this->getStoreConfig($this->xmlTrialLengthUnit, $websiteId);
    }

    /**
     * Get "Trial price" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getTrialPrice($websiteId = null)
    {
        return $this->getStoreConfig($this->xmlTrialPrice, $websiteId);
    }

    /**
     * Get "Trial start date type" config value.
     *
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @return null|string
     */
    public function getTrialStartDateType($websiteId = null)
    {
        return $this->getStoreConfig($this->xmlTrialStartDateType, $websiteId);
    }

    /**
     * Get Store Id passed to request or get current if nothing.
     *
     * @return int
     */
    public function getStoreId()
    {
        $store = null;
        $storeId = $this->request->getParam('store');
        if ($storeId) {
            if ($storeId === 'undefined') {
                $storeId = 0;
            }
            if (!is_array($storeId)) {
                $store = $this->getStore($storeId);
            }
        }
        if (!$store) {
            $store = $this->getStore(0);
        }

        return (int)$store->getId();
    }

    /**
     * Get Website Id passed to request or get current if nothing.
     *
     * @return int
     */
    public function getWebsiteId()
    {
        $website = null;
        $websiteId = $this->request->getParam('website');
        if ($websiteId) {
            if (!is_array($websiteId)) {
                $website = $this->getWebsite($websiteId);
            }
        }
        if (!$website) {
            $website = $this->getWebsite(0);
        }

        return (int)$website->getId();
    }

    /**
     * Get config value by current scope.
     *
     * If websiteId is specified it will try to get value for specified websiteId
     * Otherwise it will try to detect what is current scope and if any it will get value for current scope.
     * Current scope detected by request get parameters like "store" or "website".
     * If no websiteId specified and no current scope detected it will get value for default scope
     *
     * @param $path
     * @param null|bool|int|string|WebsiteInterface $websiteId
     * @param null|bool|int|string|StoreInterface $storeId
     * @return mixed|null|string
     */
    public function getStoreConfig($path, $websiteId = null, $storeId = null)
    {
        $result = null;
        $websiteId = $websiteId ?: $this->getWebsiteId();
        $storeId = $storeId ?: $this->getStoreId();
        $storeId = $websiteId ? null : $storeId;
        if ($storeId) {
            $result = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $this->getStore($storeId)->getCode()
            );
        } else if ($websiteId) {
            $result = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_WEBSITE,
                $this->getWebsite($websiteId)->getCode()
            );
        } else {
            $result = $this->scopeConfig->getValue($path);
        }

        return $result;
    }

    /**
     * Check if payment method is active in subscription config by code.
     *
     * @param $paymentCode
     * @param null|int $websiteId
     * @return bool
     */
    public function isPaymentAvailable($paymentCode, $websiteId = null)
    {
        $path = ActiveMethods::SECTION_ID . '/' . ActiveMethods::GROUP_ID . '/' . $paymentCode;

        return (bool)$this->getStoreConfig($path, $websiteId);
    }

    /**
     * Get list of payments codes which are active in subscription config.
     *
     * @param null|int $websiteId
     * @return array
     */
    public function getAvailablePaymentsList($websiteId = null)
    {
        $availableMethods = [];
        $path = ActiveMethods::SECTION_ID . '/' . ActiveMethods::GROUP_ID;
        $methods = $this->getStoreConfig($path, $websiteId);

        if (is_array($methods)) {
            foreach ($methods as $methodCode => $isActive) {
                if ($isActive == 1) {
                    $availableMethods[] = $methodCode;
                }
            }
        }

        return $availableMethods;
    }

    /**
     * Get title depends of active is payflow pro or paypal payments pro.
     *
     * @param null|int $websiteId
     * @return string
     */
    public function getTitleForPaypal($websiteId = null)
    {
        $title = __('Payments Pro');

        if ($this->getStoreConfig(
            'payment/' . \Magento\Paypal\Model\Config::METHOD_PAYFLOWPRO . '/active',
            $websiteId
        )) {
            $title = __('Payflow Pro');
        }

        return $title;
    }

    /**
     * Check if payment method is available for subscription and it is on for Magento.
     *
     * @param $paymentCode
     * @param null|int $storeId
     * @return bool
     */
    public function isPaymentMethodAvailableForSubscription($paymentCode, $storeId = null)
    {
        $websiteId = null;
        $isAvailable = false;
        if ($storeId){
            $websiteId = $this->getStore($storeId)->getWebsiteId();
            $this->paypalConfig->setStoreId($storeId);
        }
        if ($paymentCode === PaypalConfig::METHOD_PAYFLOWPRO || $paymentCode === PaypalConfig::METHOD_PAYMENT_PRO) {
            $isAvailableInMagento = $this->paypalConfig->isMethodAvailable($paymentCode);
        } else {
            $isAvailableInMagento = (bool)$this->getStoreConfig(
                'payment/' . $paymentCode . '/active',
                $websiteId
            );
        }
        if ($isAvailableInMagento && $this->isPaymentAvailable($paymentCode, $websiteId)) {
            $isAvailable = true;
        }

        return $isAvailable;
    }

    /**
     * Check if need check until canceled by default.
     *
     * @return bool
     */
    public function isUntilCanceledChecked()
    {
        return $this->getStoreConfig($this->xmlUntilCanceled);
    }

    /**
     * Returns store by id.
     *
     * @param int|string $storeId
     * @return StoreInterface
     */
    public function getStore($storeId)
    {
        return $this->storeManager->getStore($storeId);
    }

    /**
     * Returns website by id.
     *
     * @param int|string $websiteId
     * @return WebsiteInterface
     */
    public function getWebsite($websiteId)
    {
        return $this->storeManager->getWebsite($websiteId);
    }
}
