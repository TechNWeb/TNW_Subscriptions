<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary\Products;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;

/**
 * Configurable products additional data on Account Dashboard in Summary tab.
 *
 * @method ProductSubscriptionProfile getItem()
 */
class Configurable extends Template
{
    /**
     * Return subscription profile configurable product custom options data.
     *
     * @return array
     */
    public function getItemOptions()
    {
        $result = [];
        /** @var ProductSubscriptionProfile $item */
        $item = $this->getItem();
        $itemChildren = $item->getChildren();

        if ($itemChildren) {
            $magentoProduct = $this->getProductFromItem($item);
            $productSuperAttributes = $this->getProductSuperAttributes($magentoProduct);
            $storeId = $magentoProduct->getStoreId();

            if ($magentoProduct->getTypeId() === ConfigurableProduct::TYPE_CODE && is_array($productSuperAttributes)) {
                //We have to get custom options from child items
                foreach ($itemChildren as $itemChild) {
                    $customOptions = $itemChild->getCustomOptions();

                    if ($customOptions) {
                        $decodedOptions = \Zend_Json::decode($customOptions);

                        foreach ($productSuperAttributes as $attribute) {
                            $productAttribute = $attribute->getProductAttribute();
                            $attributeId = $productAttribute->getId();

                            if (isset($decodedOptions[$attributeId])) {
                                foreach ($attribute->getOptions() as $option) {
                                    if ($option['value_index'] == $decodedOptions[$attributeId]) {
                                        $optionLabel = $option['store_label'];
                                        break;
                                    }
                                }

                                $result[] = [
                                    'attributeLabel' => $productAttribute->getStoreLabel($storeId),
                                    'optionLabel' => $optionLabel,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Return product from subscription profile item.
     *
     * @param ProductSubscriptionProfileInterface $item
     * @return \Magento\Catalog\Model\Product|null
     */
    private function getProductFromItem(ProductSubscriptionProfileInterface $item)
    {
        try {
            return $item->getMagentoProduct();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Return current product super attributes.
     *
     * @return \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute[]
     */
    private function getProductSuperAttributes($product)
    {
        return $product->getTypeInstance()->getConfigurableAttributes($product);
    }
}
