<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\SubscriptionProfile\Checkout;

use Magento\Ui\Component\Form as BaseForm;

/**
 * Checkout messages component class.
 */
class Messages extends BaseForm
{
    /**
     * {@inheritdoc}
     */
    public function getDataSourceData()
    {
        $data = $this->getContext()->getDataProvider()->getData();

        return $data;
    }
}
