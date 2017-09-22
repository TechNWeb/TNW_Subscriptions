<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;


interface SalesExtensionAttributesInterface
{
    /**#@+
     * Constants for order and quote item extension attributes
     */
    const QUOTE_ITEM_EXTENSION_TABLE = 'tnw_subscriptions_quote_item_extension_entity';
    const ORDER_ITEM_EXTENSION_TABLE = 'tnw_subscriptions_order_item_extension_entity';
    const EXT_ATTRIBUTE_INITIAL_FEE = 'subs_initial_fee';
    const EXT_ATTRIBUTE_BASE_INITIAL_FEE = 'base_subs_initial_fee';
    const MAGENTO_ITEM_ID = 'item_id';
    /**#@-*/

    /**
     * Gets item id.
     *
     * @return string|null
     */
    public function getItemId();

    /**
     * Sets item id.
     *
     * @param string|int $itemId
     * @return $this
     */
    public function setItemId($itemId);

    /**
     * Gets initial fee.
     *
     * @return string|null
     */
    public function getSubsInitialFee();

    /**
     * Sets initial fee.
     *
     * @param string|int $initialFee
     * @return $this
     */
    public function setSubsInitialFee($initialFee);

    /**
     * Gets base initial fee.
     *
     * @return string|null
     */
    public function getBaseSubsInitialFee();

    /**
     * Sets base initial fee.
     *
     * @param string|int $baseInitialFee
     * @return $this
     */
    public function setBaseSubsInitialFee($baseInitialFee);
}