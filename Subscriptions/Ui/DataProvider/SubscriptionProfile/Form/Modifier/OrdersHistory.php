<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;

class OrdersHistory implements ModifierInterface
{
    public function modifyData(array $data)
    {
       return $data;
    }

    public function modifyMeta(array $meta)
    {
       return $meta;
    }

}