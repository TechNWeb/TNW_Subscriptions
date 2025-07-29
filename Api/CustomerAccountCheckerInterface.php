<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface for customer account checker
 */
interface CustomerAccountCheckerInterface
{
    /**
     * Check is customer exists and should be logged in for subscription purchase
     *
     * @param string $customerEmail
     * @param string|null $cartId
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isCustomerExistsAndShouldBeLoggedIn(string $customerEmail, ?string $cartId = null): int;
}
