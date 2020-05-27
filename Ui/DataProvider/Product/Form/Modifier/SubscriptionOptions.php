<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;

/**
 * Data provider for "Subscription Options" panel
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SubscriptionOptions extends AbstractModifier
{
    /**
     * Modify subscription options data
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * Unset subscription options in scheduler
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        if (array_key_exists('subscription-options', $meta)) {
            unset($meta['subscription-options']);
        }
        return $meta;
    }
}
