<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Stripe\Model\Adapter;

/**
 * Class StripeAdapter plugin to set the amount for cc auth
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
