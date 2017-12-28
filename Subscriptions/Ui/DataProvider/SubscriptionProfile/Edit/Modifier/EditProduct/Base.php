<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Edit\Modifier\EditProduct;

use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\UrlFactory;
use Magento\Framework\UrlInterface;

/**
 * Base dataProvider modifier on edit subscription form for modify subscription products data.
 */
class Base implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * Current product type
     */
    const PRODUCT_TYPE = '';

    /**
     * @var \TNW\Subscriptions\Model\ProductSubscriptionProfile
     */
    private $item;

    /**
     * URL instance
     *
     * @var UrlFactory
     */
    private $urlFactory;

    /**
     * @param UrlFactory $urlFactory
     */
    public function __construct(UrlFactory $urlFactory)
    {
        $this->urlFactory = $urlFactory;
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
            return $this->getProduct()->getTypeId() === $this::PRODUCT_TYPE;
        }

        return false;
    }

    /**
     * Return current product.
     *
     * @return MagentoProduct
     */
    public function getProduct()
    {
        return $this->getItem()->getMagentoProduct();
    }

    /**
     * Set current subscription item.
     *
     * @param \TNW\Subscriptions\Model\ProductSubscriptionProfile $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    /**
     * Return current subscription item.
     *
     * @return \TNW\Subscriptions\Model\ProductSubscriptionProfile
     */
    protected function getItem()
    {
        return $this->item;
    }

    /**
     * Return Url for Edit product's options page.
     *
     * @return string
     */
    protected function getConfigureUrl()
    {
        /** @var UrlInterface $url */
        $url = $this->urlFactory->create();
        $attributes = [];
        $attributesData = $this->getItem()->getCustomOptions();

        if (self::isJson($attributesData)) {
            $attributes = \Zend_Json::decode($attributesData);
        }

        $params = [
            'id' => $this->getItem()->getId(),
            'product_id' => $this->getItem()->getMagentoProductId(),
            'attributes' => $attributes,
        ];

        return $url->getUrl('tnw_subscriptions/subscription_products/edit'). '?' . http_build_query($params);
    }

    /**
     * Checks if value is in json format.
     *
     * @param mixed $value
     * @return bool
     */
    private static function isJson($value)
    {
        if ($value === '') {
            return false;
        }

        \json_decode($value);
        if (\json_last_error()) {
            return false;
        }

        return true;
    }
}
