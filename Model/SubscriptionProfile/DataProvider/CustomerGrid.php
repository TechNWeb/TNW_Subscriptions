<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Customer\Model\ResourceModel\Grid\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Backend\Session\Quote;
use \Magento\Customer\Model\Config\Share;

/**
 * Class CustomerGrid - dataProvider for customer grid
 */
class CustomerGrid extends AbstractDataProvider
{
    /**
     * @var Config
     */
    private $subscriptionConfig;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var Share
     */
    private $customerShare;

    /**
     * CustomerGrid constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Config $subscriptionConfig
     * @param Share $customerShare
     * @param Quote $quote
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Config $subscriptionConfig,
        Share $customerShare,
        Quote $quote,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
        $this->customerShare = $customerShare;
        $this->subscriptionConfig = $subscriptionConfig;
        $this->quote = $quote;
        $this->collection = $collectionFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function getCollection()
    {
        if (!$this->collection->isLoaded()) {
            $websiteId = $this->quote->getStore()->getWebsiteId() ?? null;
            if ($this->subscriptionConfig->getAllowAllCustomerGroups($websiteId)) {
                $customerGroups = $this->subscriptionConfig->getCustomerGroupLimit($websiteId);
                if ($customerGroups != null) {
                    $customerGroupArray = explode(',', $customerGroups);
                    $this->collection->addFieldToFilter('group_id', ['in' => $customerGroupArray]);
                    if ($this->customerShare->isWebsiteScope()) {
                        $this->collection->addFieldToFilter('website_id', $websiteId);
                    }
                }
            }
        }
        return $this->collection;
    }
}
