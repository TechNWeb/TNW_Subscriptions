<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface for customer products history management
 */
interface CustomerProductHistoryManagementInterface
{
    /**
     * Check product trial availability for specific customer
     *
     * @param int $customerId
     * @param int $productId
     * @return bool
     * @throws LocalizedException
     */
    public function isProductTrialAvailableForCustomer(int $customerId, int $productId): bool;

    /**
     * Get unique products in subscriptions history for specific customer
     *
     * @param int $customerId
     * @return array
     */
    public function getUniqueProductsInSubscriptionsForCustomer(int $customerId): array;

    /**
     * Get unique products in subscriptions history for current customer
     *
     * @return array
     */
    public function getUniqueProductsInSubscriptionsForCurrentCustomer(): array;
}
