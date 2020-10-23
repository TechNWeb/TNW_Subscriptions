<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Multishipping\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Quote\ItemGroup;

/**
 * Class Link - plugin to remove multishipping on subscription checkout
 */
class Link
{
    /**
     * @var Config
     */
    private $subscriptionsConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var \TNW\Subscriptions\Model\Quote\ItemGroup
     */
    private $quoteItemGroup;

    /**
     * Addresses constructor.
     * @param Config $subscriptionsConfig
     * @param StoreManagerInterface $storeManager
     * @param Session $checkoutSession
     * @param ItemGroup $quoteItemGroup
     */
    public function __construct(
        Config $subscriptionsConfig,
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        ItemGroup $quoteItemGroup
    ) {
        $this->quoteItemGroup = $quoteItemGroup;
        $this->checkoutSession = $checkoutSession;
        $this->subscriptionsConfig = $subscriptionsConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $subject
     * @param $html
     * @return string
     */
    public function afterGetTemplate($subject, $html)
    {
        try {
            $indexedGroups = array_filter($this->quoteItemGroup->groups(
                $this->checkoutSession->getQuote()->getAllVisibleItems()
            ), function ($key) {
                return strcasecmp($key, 'no_option') !== 0;
            }, ARRAY_FILTER_USE_KEY);
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
        } catch (\Exception $e) {
            $websiteId = null;
        }
        if ($this->subscriptionsConfig->isSubscriptionsActive($websiteId) && !empty($indexedGroups)) {
            return '';
        }
        return $html;
    }
}
