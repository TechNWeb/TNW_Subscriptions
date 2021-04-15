<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

/**
 * Interface for subscription order item extension attributes.
 */
interface QuoteItemExtensionAttributesInterface extends SalesExtensionAttributesInterface
{
    /**#@+
     * Constant for quote item extension attributes
     */
    const QUOTE_ITEM_EXTENSION_TABLE = 'tnw_subscriptions_quote_item_extension_entity';
    /**#@-*/

}
