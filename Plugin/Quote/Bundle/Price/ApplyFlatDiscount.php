<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Bundle\Price;

use Magento\Bundle\Model\Product\Price;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Plugin to apply flat discount to bundle subscription
 */
class ApplyFlatDiscount
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * ApplyFlatDiscount constructor.
     * @param RequestInterface $request
     */
    public function __construct(
        RequestInterface $request
    ) {
        $this->request = $request;
    }

    /**
     * @param Price $subject
     * @param $result
     * @param Product $bundleProduct
     * @return float
     */
    public function afterGetSelectionFinalTotalPrice(
        Price $subject,
        $result,
        $bundleProduct
    ) {
        if ($this->request->getParam('subscribe_active') === '1'
            || $bundleProduct->getCustomOption('subscription')
            || $this->request->getParam('subscription_profile_id')
            || $this->request->getParam('changed_price')
            || $this->request->getParam('namespace') == 'tnw_subscriptionprofile_create_add_product_modal_form'
            || $this->request->getParam('namespace') == 'tnw_subscriptionprofile_create_modify_modal_form'
        ) {
            if ($bundleProduct->getData(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE) === '1'
                && $bundleProduct->getData(Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT) === '1'
                && $bundleProduct->getData(Attribute::SUBSCRIPTION_DISCOUNT_TYPE) === '2'
            ) {
                $result *= (100 - (float)$bundleProduct->getData(Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT))/100;
            }
        }
        return $result;
    }
}
