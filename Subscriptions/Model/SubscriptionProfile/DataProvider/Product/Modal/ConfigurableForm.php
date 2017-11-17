<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\Api\Filter;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Configure profuct to add to subscription.
 */
class ConfigurableForm extends AbstractDataProvider
{
    /**#@+
     * Form request values
     */
    const FORM_DATA_KEY = 'add_product_modal_configurable_form_data';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    /**#@+
     * Form data scope
     */
    const DATA_SCOPE_CONFIGURABLE_MODAL_FORM = 'tnw_subscriptionprofile_create_add_product_modal_configurable_form';
    /**#@-*/

    /**#@+
     * Qty field default value
     */
    const DEFAULT_QTY_VALUE = 1;
    /**#@-*/

    protected $scopeName;
    /** @var [] */
    protected $loadedData;
    /** @var UrlInterface */
    protected $urlBuilder;
    /** @var StepPool */
    protected $stepPool;
    /** @var Registry */
    protected $registry;
    /** @var MagentoProduct */
    private $currentProduct;
    /** @var PoolInterface */
    private $pool;

    /**
     * Data providers form context.
     *
     * @var Context
     */
    protected $formContext;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param Registry $registry
     * @param Context $formContext
     * @param PoolInterface $pool
     * @param string $scope
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlInterface $urlBuilder,
        StepPool $stepPool,
        Registry $registry,
        Context $formContext,
        PoolInterface $pool,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->registry = $registry;
        $this->pool = $pool;
        $this->formContext = $formContext;
        $this->scopeName = $scope ? $scope : self::DATA_SCOPE_CONFIGURABLE_MODAL_FORM .'.'.  self::DATA_SCOPE_CONFIGURABLE_MODAL_FORM;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta,
            $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $data = [];
        $qtyToShow = self::DEFAULT_QTY_VALUE;

        if ($this->isSubscriptionPresetQty()) {
            $productId = $this->getProductId();
            $productRecurringOptions =$this->formContext
                ->getRecurringOptionRepository()
                ->getListByProductId($productId)
                ->getItems();
            $changedQty = false;

            foreach ($productRecurringOptions as $option) {
                if ($option->getDefaultBillingFrequency()) {
                    $qtyToShow = $option->getPresetQty();
                    break;
                }
                if (!$changedQty) {
                    $qtyToShow = $option->getPresetQty();
                }
            }
        }

        $data[self::FORM_DATA_VALUE]['qty'] = $qtyToShow;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        /** @var array $meta */
        $meta = parent::getMeta();

        $meta = array_merge_recursive(
            $meta,
            [
                'general' => [
                    'children'=> [
                        'qty' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'disabled' => $this->isSubscriptionPresetQty(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ]
        );

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {

    }

    /**
     * Return current product id from request.
     *
     * @return int
     */
    private function getProductId()
    {
        return (int)$this->formContext->getRequest()->getParam('product_id', 0);
    }

    /**
     * Returns product.
     *
     * @return MagentoProduct
     */
    private function getCurrentProduct()
    {
        if (!$this->currentProduct) {
            $productId = $this->getProductId();
            $this->currentProduct = $this->formContext->getProductRepository()->getById($productId);
            $this->registry->register('product', $this->currentProduct);
        }

        return $this->currentProduct;
    }

    /**
     * Check product preset qty field.
     *
     * @return bool
     */
    private function isSubscriptionPresetQty()
    {
        $product = $this->getCurrentProduct();

        return (bool)$product->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
    }
}
