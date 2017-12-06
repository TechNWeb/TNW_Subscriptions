<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Product\ListProduct;

use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as FrequencyOptionRepository;

/**
 *  Subscription Product list.
 */
class ListProductButtons extends Template
{
    /**
     * Subscription module config
     *
     * @var Config
     */
    private $config;

    /**
     * Modal form for adding single product to subscription
     *
     * @var FrequencyOptionRepository
     */
    private $frequencyOptionRepository;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param FrequencyOptionRepository $frequencyOptionRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        FrequencyOptionRepository $frequencyOptionRepository,
        array $data = []
    ) {
        $this->config = $config;
        $this->frequencyOptionRepository = $frequencyOptionRepository;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current product.
     *
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getCurrentProduct()
    {
        return $this->getProduct() ? $this->getProduct() : null;
    }

    /**
     * Return position for actions regarding image size changing in vde if needed.
     *
     * @return string|null
     */
    public function getCurrentPosForActions()
    {
        return $this->getPos() ? $this->getPos() : null;
    }

    /**
     * Return current list view mode.
     *
     * @return string
     */
    public function getCurrentViewMode()
    {
        return $this->getViewMode() ? $this->getViewMode() : "";
    }

    /**
     * Return current product in list position.
     *
     * @return string
     */
    public function getCurrentPosition()
    {
        return $this->getPosition() ? $this->getPosition() : "";
    }

    /**
     * Return current product post params.
     *
     * @return array
     */
    public function getCurrentPostParams()
    {
        return $this->getPostParams() ? $this->getPostParams() : [];
    }

    /**
     * Get "Enable Subscriptions" config value for current website
     *
     * @return bool
     */
    public function isSubscribeAvailable($product)
    {
        return
            $this->config->isSubscriptionsActiveCurrent()
            && !empty($this->getProductBillingFrequencies($product))
            && $this->getRequest()->getRouteName() !== 'checkout';
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" only.
     *
     * @return bool
     */
    public function isOnlySubscribePurchase($product)
    {
        return ($this->getProductSubscriptionPurchaseType($product) == PurchaseType::RECURRING_PURCHASE_TYPE)
            && $product->getIsSalable();
    }

    /**
     * Return subscription purchase type.
     *
     * @param $product
     * @return int|null
     */
    private function getProductSubscriptionPurchaseType($product)
    {
        return $product->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE);
    }

    /**
     * Returns list of product billing frequencies.
     *
     * @return array
     */
    private function getProductBillingFrequencies($product)
    {
            $productId = $product->getId();
            $productBillingFrequencies = $this->frequencyOptionRepository
                ->getListByProductId($productId)
                ->getItems();

        return $productBillingFrequencies;
    }
}
