<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Customer\Model\ResourceModel\Grid\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use TNW\Subscriptions\Model\Config;
use Magento\Framework\Message\ManagerInterface;
use TNW\Subscriptions\Model\Backend\Session\Quote;

/**
 * Class CustomerGrid - dataProvider for customer grid
 */
class CustomerGrid extends AbstractDataProvider
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Config
     */
    private $subscriptionConfig;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * CustomerGrid constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param Session $customerSession
     * @param Config $subscriptionConfig
     * @param ManagerInterface $messageManager
     * @param Quote $quote
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        Session $customerSession,
        Config $subscriptionConfig,
        ManagerInterface $messageManager,
        Quote $quote,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->customerSession = $customerSession;
        $this->subscriptionConfig = $subscriptionConfig;
        $this->messageManager = $messageManager;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
        $websiteId = $quote->getStore()->getWebsiteId() ? $quote->getStore()->getWebsiteId() : null;
        if ($this->subscriptionConfig->getAllowAllCustomerGroups($websiteId)) {
            $customerGroups = $this->subscriptionConfig->getCustomerGroupLimit($websiteId);
            if ($customerGroups != null) {
                $customerGroupArray = explode(',', $customerGroups);
                $this->collection = $collectionFactory->create()
                    ->addFieldToFilter('group_id', ['in' => $customerGroupArray])
                    ->addFieldToFilter('website_id', $websiteId);
                if (!$this->collection->getData()) {
                    $this->messageManager->addErrorMessage(__(
                        'No available customers are found,
                        please check the included customer groups in the configuration.'
                    ));
                }
                return $this->collection;
            }
        }
    }
}
