<?php

/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

/**
 * Interface for subscription order item extension attributes.
 */
interface InvoiceItemExtensionAttributesInterface extends SalesExtensionAttributesInterface
{
    /**#@+
     * Constant for invoice item extension attributes
     */
    const INVOICE_ITEM_EXTENSION_TABLE = 'tnw_subscriptions_invoice_item_extension_entity';
    /**#@-*/

}
