<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Pricing\Render;

use TNW\Subscriptions\Model\Product\Attribute;
use Magento\Catalog\Pricing\Render;

class FinalPrice extends Render
{
    protected function _toHtml()
    {
        $result = parent::_toHtml();
        if (
            $this->getProduct()->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE) == "1"
            && (bool) stripos($result, 'price-subscription_price')
        ) {
            $result = '';
        }
        return $result;
    }
}
