<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\EditProduct;

use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context as FormContext;

/**
 * DataProvider modifier on add to subscription form for bundle products.
 */
class Bundle extends Base
{
    /**
     * Current product type
     */
    const PRODUCT_TYPE = Type::TYPE_BUNDLE;

    /**
     * Options container prefix
     */
    const CONTAINER_PREFIX = 'bundle_options';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * URL instance
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Configurable constructor.
     * @param FormContext $formContext
     * @param Registry $registry
     * @param UrlInterface $urlBuilder
     * @param SerializerInterface $serializer
     */
    public function __construct(
        FormContext $formContext,
        Registry $registry,
        UrlInterface $urlBuilder,
        SerializerInterface $serializer
    ) {
        $this->registry = $registry;
        $this->urlBuilder = $urlBuilder;

        parent::__construct($formContext, $serializer);
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        if ($this->isUsedModifier()) {
            $data['bundle_option'] = $this->getItem()->getBuyRequest()->getBundleOption();
            $data['bundle_option_qty'] = $this->getItem()->getBuyRequest()->getBundleOptionQty();
            $data[self::CONTAINER_PREFIX] = $this->getItem()->getBuyRequest()->getBundleOption();
            foreach ($this->getBundleOrderOptions() as $index => $bundleOrderOption) {
                $optionValue = '';
                foreach ($bundleOrderOption['value'] as $value) {
                    $optionValue .= $value['qty'] . ' x ' . $value['title'] . '<br>';
                }

                $data[self::CONTAINER_PREFIX][$index] = $optionValue;
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        if ($this->isUsedModifier()) {
            $meta = array_merge_recursive(
                $meta,
                [
                    'children' => [
                        'form' => [
                            'children' => [
                                'description_fieldset' => [
                                    'children' => [
                                        'left_container' => $this->editOptionsButtonMeta(),
                                        'middle_container' => $this->getProductOptionsMeta(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        return $meta;
    }

    /**
     * Return bundle product options meta data.
     *
     * @return array
     */
    private function getProductOptionsMeta()
    {
        return [
            'children' => [
                'options' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => false,
                                'collapsible' => false,
                                'componentType' => Fieldset::NAME,
                                'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                                'sortOrder' => 100,
                                'dataScope' => self::CONTAINER_PREFIX,
                                'additionalClasses' => 'product-options',
                            ],
                        ],
                    ],
                    'children' => $this->getAttributesMeta(),
                ],
            ],
        ];
    }

    /**
     * Return super attributes meta data.
     *
     * @return array
     */
    private function getAttributesMeta()
    {
        $result = [];
        $iterator = 0;

        $bundleOptions = $this->getBundleOrderOptions();
        foreach ($bundleOptions as $bundleOption) {
            $iterator++;
            $result[self::CONTAINER_PREFIX . $bundleOption['option_id']] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => $bundleOption['label'] . ':',
                            'collapsible' => false,
                            'componentType' => Field::NAME,
                            'formElement' => Input::NAME,
                            'additionalClasses' => 'edit-product',
                            'previewElementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                            'sortOrder' => $iterator,
                            'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                            'showPreview' => true,
                            'dataScope' => $bundleOption['option_id'],
                        ],
                    ],
                ],
            ];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getBundleOrderOptions()
    {
        return $this->getItem()->getProduct()->getTypeInstance()->getOrderOptions(
            $this->getItem()->getProduct()
        )['bundle_options'];
    }

    /**
     * Return 'Edit options' button meta data.
     *
     * @return array
     */
    private function editOptionsButtonMeta()
    {
        $currentFormName = $this->registry->registry('form_full_name');
        $leftContainerName = $currentFormName . '.description_fieldset.left_container';
        $encodedAttributes = $this->serializer->serialize([
            'bundle_option' => $this->getItem()->getBuyRequest()->getBundleOption(),
            'bundle_option_qty' => $this->getItem()->getBuyRequest()->getBundleOptionQty()
        ]);

        return [
            'children' => [
                'edit_options' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => UiContainer::NAME,
                                'componentType' => UiContainer::NAME,
                                'component' => 'TNW_Subscriptions/js/components/options-button',
                                'additionalClasses' => 'edit-options-button action-advanced action-additional',
                                'additionalForGroup' => true,
                                'displayAsLink' => true,
                                'title' => '[' . __('Edit options') . ']',
                                'actions' => [
                                    [
                                        'targetName' => $leftContainerName . '.edit_options',
                                        'actionName' => 'editOptions',
                                        'params' =>  [
                                            $this->getItem()->getProductId(), //product id
                                            $this->getItem()->getId(),  //subscription item id
                                            $this->getItem()->getQuoteId(),  // quote id
                                            $encodedAttributes // encoded params
                                        ],
                                    ],
                                ],
                                'configureUrl' => $this->getConfigureUrl(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Return Url for Edit configurable product's options page.
     *
     * @return string
     */
    private function getConfigureUrl()
    {
        return $this->urlBuilder->getUrl('tnw_subscriptions/cart/configure', [
            'id' => $this->getItem()->getId(),
            'product_id' => $this->getItem()->getProduct()->getId()
        ]);
    }
}
