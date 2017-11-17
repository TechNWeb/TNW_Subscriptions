<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\Product;

use Magento\Catalog\Model\Product as MagentoProduct;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context;

/**
 * \TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Form base modifier.
 */
class Base implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /** Current product type */
    const PRODUCT_TYPE = '';

    /**
     * Data providers form context.
     *
     * @var Context
     */
    protected $formContext;

    /**
     * Current product.
     *
     * @var MagentoProduct
     */
    private $product;

    /**
     * @param Context $formContext
     */
    public function __construct(Context $formContext) {
        $this->formContext = $formContext;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Checks if current modifier can be used. Depends on product type.
     *
     * @return bool
     */
    protected function isUsedModifier()
    {
        if ($this->getProduct()) {
            return $this->getProduct()->getTypeId() == $this::PRODUCT_TYPE;
        }

        return false;
    }

    /**
     * Return current product.
     *
     * @return bool|MagentoProduct
     */
    protected function getProduct()
    {
        if ($this->product !== false) {
            $productId = $this->getProductId();
            $this->product = false;
            if ($productId) {
                $this->product = $this->formContext->getProductRepository()->getById($productId);
            }
        }

        return $this->product;
    }

    /**
     * Return current product Id.
     *
     * @return string|null
     */
    private function getProductId()
    {
        return $this->formContext->getRequest()->getParam('product_id');
    }
}
