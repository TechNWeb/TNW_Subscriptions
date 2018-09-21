<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Product;

use Magento\Catalog\Block\Product\ListProduct as OrigListProduct;

/**
 *  Subscription Product list.
 */
class ListProduct extends OrigListProduct
{
    /**
     * Params witch added to button block.
     *
     * @var array
     */
    private $postParamsToButtonsBlock = [];

    /**
     * Prepare params too add button block.
     *
     * @param $product
     * @param String $pos
     * @param String $viewMode
     * @param String $position
     * @param array $postParams
     * @return void
     */
    public function prepareParamsToButtonsBlock($product, $pos, $viewMode, $position, $postParams)
    {
        $this->postParamsToButtonsBlock = [
            'data' => [
                'product' => $product,
                'pos' => $pos,
                'view_mode' => $viewMode,
                'position' => $position,
                'post_params' => $postParams
            ]
        ];
    }

    /**
     * Create and return buttons block HTML with params.
     *
     * @return string
     */
    public function getButtonsHtml()
    {
        $buyButtonsBlock = $this->getLayout()->createBlock(
            \TNW\Subscriptions\Block\Product\ListProduct\ListProductButtons::class,
            'category.products.list_' . $this->postParamsToButtonsBlock['data']['product']->getId(),
            $this->postParamsToButtonsBlock
        )->setTemplate('TNW_Subscriptions::product/list/buttons.phtml');
        return $buyButtonsBlock->toHtml();
    }
}
