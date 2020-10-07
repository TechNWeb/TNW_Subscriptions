<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Pricing\Render;

use TNW\Subscriptions\Model\Product\Attribute;
use Magento\Catalog\Pricing\Render;

/**
 * Class FinalPrice - final price renderer
 */
class FinalPrice extends Render
{
    /**
     * @return string
     */
    protected function _toHtml()
    {
        $result = parent::_toHtml();
        if ($this->getProduct()->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE) == "1"
            && stripos($result, 'price-subscription_price') !== false
        ) {
            $result = '';
        }
        return $result;
    }
}
