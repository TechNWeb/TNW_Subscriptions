<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Save currency processor.
 */
class Currency extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        if (isset($data['currency_id'])) {
            $this->getSession()->setCurrencyId($data['currency_id']);
            $this->getSubCreateModel()->setCurrency($data['currency_id']);
        }
    }
}
