<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Save coupon code processor.
 */
class Coupon extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        if (isset($data['coupon_code'])) {
            $this->errors = $this->getSubCreateModel()
                ->setCouponCode($data['coupon_code'] !== '' ? $data['coupon_code'] : null);
        }
    }
}
