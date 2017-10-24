<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout;

use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

/**
 * Class ProductListing data provider
 */
class ProductListing extends Product
{
    /**
     * Listing image id.
     */
    const LISTING_RENDER_URL = 'tnw_subscriptions/ui/render';

    /**
     * Listing image id.
     */
    const LISTING_IMAGE_ID = 'product_thumbnail_image';

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        return [];
    }
}
