<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Request\Save\Profile;

/**
 * Save customer processor.
 */
class Customer extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        if (isset($data['customer_id'])) {
            $this->getSession()->setCustomerId($data['customer_id']);
            $this->getSession()->setCreateNewCustomer(null);
        }

        if (isset($data['create_new_customer'])) {
            $this->getSession()->setCreateNewCustomer($data['create_new_customer']);
            $this->getSession()->setCustomerId(null);
        }

        if (isset($data['customer_id']) || isset($data['create_new_customer'])) {
            $this->getSubCreateModel()->changeCustomerInQuote();
        }
    }
}