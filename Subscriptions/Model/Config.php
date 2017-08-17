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
use TNW\Subscriptions\Block\Adminhtml\System\Config\PaymentMethods\ActiveMethods;

class Config
{
    /**#@+
     * Config xml path for General section
     */
    private $xmlPurchaseType = 'tnw_subscriptions_product/general/purchase_type';
    private $xmlStartDateType = 'tnw_subscriptions_product/general/start_date_type';
    private $xmlLockProductPriceStatus = 'tnw_subscriptions_product/general/lock_product_price_status';
    private $xmlUnlockPresetQty = 'tnw_subscriptions_product/general/unlock_preset_qty_status';
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

    /**
     * Config xml path for is Subscription module active.
     *
     * @var string
     */
    private $xmlIsModuleEnable = 'tnw_subscriptions_general/general/active';


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
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Http $request
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Http $request
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->request = $request;
    }

    #region General section
    /**
     * Get "Purchase type" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return null|string
     */
    public function purchaseType($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlPurchaseType, $websiteId);

        return $value;
    }

    /**
     * Get "Start date type" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return null|string
     */
    public function startDateType($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlStartDateType, $websiteId);

        return $value;
    }

    /**
     * Get "Lock product price status" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return bool
     */
    public function lockProductPriceStatus($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlLockProductPriceStatus, $websiteId);

        return $value ? true : false;
    }

    /**
     * Get "Unlock Preset Qty" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return bool
     */
    public function unlockPresetQtyStatus($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlUnlockPresetQty, $websiteId);

        return $value ? true : false;
    }
    #endregion

    #region Discount section
    /**
     * Get "Offer flat discount status" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return bool
     */
    public function offerFlatDiscountStatus($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlOfferFlatDiscountStatus, $websiteId);

        return $value ? true : false;
    }

    /**
     * Get "Discount amount" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return null|string
     */
    public function discountAmount($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlDiscountAmount, $websiteId);

        return $value;
    }

    /**
     * Get "Discount type" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return null|string
     */
    public function discountType($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlDiscountType, $websiteId);

        return $value;
    }
    #endregion

    #region Trial section
    /**
     * Get "Trial status" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return bool
     */
    public function trialStatus($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlTrialStatus, $websiteId);

        return $value ? true : false;
    }

    /**
     * Get "Trial length" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return null|string
     */
    public function trialLength($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlTrialLength, $websiteId);

        return $value;
    }

    /**
     * Get "Trial length unit" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return null|string
     */
    public function trialLengthUnit($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlTrialLengthUnit, $websiteId);

        return $value;
    }

    /**
     * Get "Trial price" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return null|string
     */
    public function trialPrice($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlTrialPrice, $websiteId);

        return $value;
    }

    /**
     * Get "Trial start date type" config value.
     *
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @return null|string
     */
    public function trialStartDateType($websiteId = null)
    {
        $value = $this->getStoreConfig($this->xmlTrialStartDateType, $websiteId);

        return $value;
    }
    #endregion

    #region Common methods to get config values
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
            if ($storeId == 'undefined') {
                $storeId = 0;
            }
            if (!is_array($storeId)) {
                $store = $this->storeManager->getStore($storeId);
            }
        }
        if (!$store) {
            $store = $this->storeManager->getStore(0);
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
                $website = $this->storeManager->getWebsite($websiteId);
            }
        }
        if (!$website) {
            $website = $this->storeManager->getWebsite(0);
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
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $websiteId
     * @param null|bool|int|string|\Magento\Store\Api\Data\StoreInterface $storeId
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
                $this->storeManager->getStore($storeId)->getCode()
            );
        } else if ($websiteId) {
            $result = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_WEBSITE,
                $this->storeManager->getWebsite($websiteId)->getCode()
            );
        } else {
            $result = $this->scopeConfig->getValue($path);
        }

        return $result;
    }
    #endregion

    /**
     * Check if Subscription module is enabled.
     *
     * @return bool
     */
    public function isModuleEnabled()
    {
        return (bool)$this->getStoreConfig($this->xmlIsModuleEnable);
    }

    /**
     * Check if payment method is active in subscription config by code.
     *
     * @param string $paymentCode
     * @return bool
     */
    public function isPaymentAvailable($paymentCode)
    {
        $path = ActiveMethods::SECTION_ID . '/' . ActiveMethods::GROUP_ID . '/' . $paymentCode;

        return (bool)$this->getStoreConfig($path);
    }

    /**
     * Get list of payments codes which are active in subscription config.
     *
     * @return array
     */
    public function getAvailablePaymentsList()
    {
        $availableMethods = [];

        $path = ActiveMethods::SECTION_ID . '/' . ActiveMethods::GROUP_ID;

        $methods = $this->getStoreConfig($path);

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
     * Check if module enable and if at least one payment method is available for subscription.
     *
     * @return bool
     */
    public function isActive()
    {
        $isActive = false;
        if ($this->isModuleEnabled() && !isEmpty($this->getAvailablePaymentsList())) {
            $isActive = true;
        }

        return $isActive;
    }
}
