<?php

namespace TNW\Subscriptions\Plugin\Block\Product;

use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Framework\Exception\LocalizedException;
use TNW\Subscriptions\Block\Product\ListProduct as TnwListProduct;

/**
 * Plugin is used to inject subscription block to ProductList widget
 * and to change template
 */
class ProductsListWidget
{
    /**
     * @param ProductsList $subject
     * @return array
     * @throws LocalizedException
     */
    public function beforeToHtml(ProductsList $subject)
    {
        $subject->setTemplate('TNW_Subscriptions::product/widget/content/grid.phtml');
        $childBlock = $subject->getLayout()->createBlock(
            TnwListProduct::class,
            $subject->getNameInLayout() . '.tnw.list'
        );
        $subject->insert($childBlock);
        return [];
    }
}
