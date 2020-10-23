<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Multishipping\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Quote\ItemGroup;

/**
 * Class Addresses - plugin to restrict multishipping on subscription orders
 */
class Addresses
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var ManagerInterface
     */
    private $managerInterface;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Context
     */
    private $context;

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
     * @var ItemGroup
     */
    private $quoteItemGroup;

    /**
     * Addresses constructor.
     * @param ResultFactory $resultFactory
     * @param ManagerInterface $managerInterface
     * @param UrlInterface $urlBuilder
     * @param Context $context
     * @param Config $subscriptionsConfig
     * @param StoreManagerInterface $storeManager
     * @param Session $checkoutSession
     * @param ItemGroup $quoteItemGroup
     */
    public function __construct(
        ResultFactory $resultFactory,
        ManagerInterface $managerInterface,
        UrlInterface $urlBuilder,
        Context $context,
        Config $subscriptionsConfig,
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        ItemGroup $quoteItemGroup
    ) {
        $this->quoteItemGroup = $quoteItemGroup;
        $this->checkoutSession = $checkoutSession;
        $this->subscriptionsConfig = $subscriptionsConfig;
        $this->managerInterface = $managerInterface;
        $this->resultFactory = $resultFactory;
        $this->urlBuilder = $urlBuilder;
        $this->context = $context;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Multishipping\Controller\Checkout\Addresses $subject
     * @return mixed
     */
    public function afterExecute(\Magento\Multishipping\Controller\Checkout\Addresses $subject)
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
        if ($this->subscriptionsConfig->isSubscriptionsActive($websiteId)
            && !empty($indexedGroups)
        ) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $this->managerInterface->addWarningMessage(__('Checkout with multiple addresses is not supported.'));
            return $resultRedirect->setUrl($this->urlBuilder->getUrl('checkout/cart', ['_secure' => true]));
        }
    }
}
