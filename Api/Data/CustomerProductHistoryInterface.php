<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

/**
 * Interface determines the functionality to be implemented for customer products history list
 */
interface CustomerProductHistoryInterface
{
    /**#@+
     * Main table name.
     */
    const CUSTOMER_PRODUCT_HISTORY_TABLE = 'tnw_subscriptions_customer_product_history';
    /**#@-*/

    /**#@+
     * Constants for field names
     */
    const ID = 'id';
    const SUBSCRIPTION_PROFILE_ID = 'subscription_profile_id';
    const CUSTOMER_ID = 'customer_id';
    const MAGENTO_PRODUCT_ID = 'magento_product_id';
    /**#@-*/

    /**
     * Gets id.
     *
     * @return int|null
     */
    public function getId();

    /**
     * Sets id.
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Gets subscription profile id.
     *
     * @return int|null
     */
    public function getProfileId();

    /**
     * Sets subscription profile id.
     *
     * @param int $profileId
     * @return $this
     */
    public function setProfileId($profileId);

    /**
     * Gets customer id.
     *
     * @return int|null
     */
    public function getCustomerId();

    /**
     * Sets customer id.
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * Gets Magento product id.
     *
     * @return int|null
     */
    public function getMagentoProductId();

    /**
     * Sets Magento product id.
     *
     * @param int $productId
     * @return $this
     */
    public function setMagentoProductId($productId);
}
