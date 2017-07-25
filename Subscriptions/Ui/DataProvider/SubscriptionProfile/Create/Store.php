<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;


class Store implements OptionSourceInterface
{
    /** @var StoreManagerInterface */
    protected $storeManager;

    /**
     * DataProvider constructor.
     *
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    public function toOptionArray()
    {
        return $this->getStoreRadioOptions();
    }

    protected function getStoreRadioOptions()
    {
        $result = [];

        $websiteCollection = $this->storeManager->getWebsites();
        $groupCollection = $this->storeManager->getGroups();
        $storeCollection = $this->storeManager->getStores();

        /** @var \Magento\Store\Model\Website $website */
        foreach ($websiteCollection as $website) {
            /** @var \Magento\Store\Model\Group $group */
            foreach ($groupCollection as $group) {
                if ($group->getWebsiteId() == $website->getId()) {
                    /** @var  \Magento\Store\Model\Store $store */
                    foreach ($storeCollection as $store) {
                        if ($store->getGroupId() == $group->getId()) {
                            $resultLabel = $website->getName() . ' \\ ' . $group->getName() . ' \\ ' . $store->getName();

                            $result[] = [
                                'label' => $resultLabel,
                                'value' => $store->getId()
                            ];
                        }
                    }
                }
            }
        }

        return $result;
    }
}
