<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Stripe\Model\Adapter;

/**
 * Class StripeAdapter
 * @package TNW\Subscriptions\Plugin\Stripe\Model\Adapter
 */
class StripeAdapter
{
    /**
     * @param $subject
     * @param $attributes
     * @return array
     */
    public function beforeCreatePaymentIntent($subject, $attributes)
    {
        if (isset($attributes['amount']) && $attributes['amount'] < 1) {
            $attributes['amount'] = 100;
        }
        return [$attributes];
    }
}
