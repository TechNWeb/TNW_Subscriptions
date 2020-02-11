<?php

namespace TNW\Subscriptions\Plugin\Catalog\Block\Product;

use TNW\Subscriptions\Model\ProductBillingFrequency\DataProvider as ProductBillingFrequency;

class ListProduct
{
    /**
     * @var ProductBillingFrequency
     */
    private $productBillingFrequency;

    /**
     * ListProduct constructor.
     * @param ProductBillingFrequency $productBillingFrequency
     */
    public function __construct(
        ProductBillingFrequency $productBillingFrequency
    ) {
        $this->productBillingFrequency = $productBillingFrequency;
    }

    /**
     * @param \Magento\Catalog\Block\Product\ListProduct $subject
     * @param $product
     */
    public function beforeGetProductPrice(\Magento\Catalog\Block\Product\ListProduct $subject, $product)
    {
        $productsData = $this->productBillingFrequency->getData();
        foreach ($productsData as $productData) {
            if ($productData['default_billing_frequency'] == 1 &&
                $productData['magento_product_id'] == $product->getData()['entity_id']
            ) {
                $product->setData('price', $productData['price']);
            }
        }
    }

}
