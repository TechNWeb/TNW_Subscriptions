<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Block\Product;

use Magento\Catalog\Block\Product\Widget\NewWidget;
use Magento\Framework\Exception\LocalizedException;
use TNW\Subscriptions\Block\Product\ListProduct as TnwListProduct;

/**
 * Plugin is used to inject subscription block to NewProducts widget
 * and to change template
 */
class NewProductsWidget
{
    /**
     * @param NewWidget $subject
     * @return array
     * @throws LocalizedException
     */
    public function beforeToHtml(NewWidget $subject)
    {
        switch ($subject->getTemplate()) {
            case 'product/widget/new/content/new_grid.phtml':
                $subject->setTemplate('TNW_Subscriptions::product/widget/new/content/new_grid.phtml');
                break;

            case 'product/widget/new/content/new_list.phtml':
                $subject->setTemplate('TNW_Subscriptions::product/widget/new/content/new_list.phtml');
                break;
        }

        $childBlock = $subject->getLayout()->createBlock(
            TnwListProduct::class,
            $subject->getNameInLayout() . '.tnw.list'
        );
        $subject->insert($childBlock);
        return [];
    }
}
