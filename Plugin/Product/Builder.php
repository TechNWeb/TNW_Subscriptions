<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Product;

use TNW\Subscriptions\Model\ProductDefaultAttributes;

/**
 * Product builder interceptor
 */
class Builder
{
    /**
     * Product default attributes helper.
     *
     * @var ProductDefaultAttributes
     */
    private $productDefaultAttributes;

    /**
     * @param ProductDefaultAttributes $productDefaultAttributes
     */
    public function __construct(
        ProductDefaultAttributes $productDefaultAttributes
    ) {
        $this->productDefaultAttributes = $productDefaultAttributes;
    }

    /**
     * Prepare default values for newly created product.
     *
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Builder $subject
     * @param \Magento\Catalog\Model\Product $result
     * @return \Magento\Catalog\Model\Product
     */
    public function afterBuild(
        \Magento\Catalog\Controller\Adminhtml\Product\Builder $subject,
        \Magento\Catalog\Model\Product $result
    ) {
        if ($result->isObjectNew()) {
            $this->productDefaultAttributes->setDefaultValues($result);
        }

        return $result;
    }
}
