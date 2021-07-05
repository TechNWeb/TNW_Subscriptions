<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Product;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use TNW\Subscriptions\Api\CustomerProductHistoryManagementInterface;

/**
 * Class CustomerProductsHistoryList - provides trial checking functionality for views
 */
class CustomerProductsHistoryList implements ArgumentInterface
{
    /**
     * @var CustomerProductHistoryManagementInterface
     */
    private $customerProductHistoryManagement;

    /**
     * CustomerProductsHistoryList constructor.
     * @param CustomerProductHistoryManagementInterface $customerProductHistoryManagement
     */
    public function __construct(
        CustomerProductHistoryManagementInterface $customerProductHistoryManagement
    ) {
        $this->customerProductHistoryManagement = $customerProductHistoryManagement;
    }

    /**
     * @param int $productId
     * @return bool
     */
    public function isProductTrialAvailableForCurrentCustomer(int $productId): bool
    {
        $customerProductHistoryList = $this->getCurrentCustomerProductHistoryList();
        return !in_array($productId, $customerProductHistoryList, true);
    }

    /**
     * @return array
     */
    public function getCurrentCustomerProductHistoryList(): array
    {
        return $this->customerProductHistoryManagement->getUniqueProductsInSubscriptionsForCurrentCustomer();
    }
}
