<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\Registry;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

/**
 * Modal form for editing product options.
 */
class EditProductOptions extends AbstractDataProvider
{
    /**#@+
     * Form request values
     */
    const FORM_DATA_KEY = 'add_product_modal_edit_product_options_form_data';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    /**#@+
     * Form data scope
     */
    const DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM = 'tnw_subscriptionprofile_create_add_product_modal_edit_product_options_form';
    /**#@-*/

    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * Data providers form context.
     *
     * @var Context
     */
    private $formContext;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param PoolInterface $pool
     * @param Context $formContext
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        PoolInterface $pool,
        Context $formContext,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->pool = $pool;
        $this->formContext = $formContext;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta =  [
            'item_data' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => false,
                            'collapsible' => false,
                            'componentType' => Fieldset::NAME,
                            'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                            'sortOrder' => 10,
                            'dataScope' => 'item_data',
                        ],
                    ],
                ],
                'children' => [
                    'product_id' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => false,
                                    'collapsible' => false,
                                    'componentType' => Field::NAME,
                                    'formElement' => Input::NAME,
                                    'dataScope' => 'product_id',
                                    'sortOrder' => 10,
                                    'additionalClasses' => 'hidden',
                                ],
                            ],
                        ],
                    ],
                    'item_id' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => false,
                                    'collapsible' => false,
                                    'componentType' => Field::NAME,
                                    'formElement' => Input::NAME,
                                    'dataScope' => 'item_id',
                                    'sortOrder' => 20,
                                    'additionalClasses' => 'hidden',
                                ],
                            ],
                        ],
                    ],
                    'quote_id' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => false,
                                    'collapsible' => false,
                                    'componentType' => Field::NAME,
                                    'formElement' => Input::NAME,
                                    'dataScope' => 'quote_id',
                                    'sortOrder' => 30,
                                    'additionalClasses' => 'hidden',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return $data = [
            self::FORM_DATA_VALUE => [
                'item_data' => [
                    'product_id' => $this->getProductId(),
                    'item_id' => $this->getItemId(),
                    'quote_id' => $this->getQuoteId(),
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {

    }

    /**
     * Return product id for request.
     *
     * @return int
     */
    private function getProductId()
    {
        return (int)$this->formContext->getRequest()->getParam('product_id', 0);
    }

    /**
     * Return quote item id from request.
     *
     * @return int
     */
    private function getItemId()
    {
        return (int)$this->formContext->getRequest()->getParam('item_id', 0);
    }

    /**
     * Return quote id from request.
     *
     * @return int
     */
    private function getQuoteId()
    {
        return (int)$this->formContext->getRequest()->getParam('quote_id', 0);
    }
}
