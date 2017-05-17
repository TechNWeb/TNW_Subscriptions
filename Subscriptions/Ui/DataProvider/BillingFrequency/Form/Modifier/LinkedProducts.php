<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Form\Modifier;

use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;


/**
 * Class LinkedProducts
 */
class LinkedProducts extends AbstractModifier
{
    const DATA_SCOPE_LINKED_PRODUCTS = 'linked';
    const GROUP_LINKED_PRODUCTS = 'linked';
    const DEFAULT_SCOPE_NAME = 'tnw_billingfrequency_form.tnw_billingfrequency_form';

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var string */
    protected $scopeName;

    /** @var Registry */
    protected $registry;

    /** @var ProductBillingFrequencyRepositoryInterface */
    protected $productBillingFrequencyRepository;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var ImageHelper */
    protected $imageHelper;

    /** @var Status */
    protected $status;

    /** @var AttributeSetRepositoryInterface */
    protected $attributeSetRepository;

    /**
     * LinkedProducts constructor.
     * @param UrlInterface $urlBuilder
     * @param Registry $coreRegistry
     * @param ProductRepositoryInterface $productRepository
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository
     * @param string $scopeName
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Registry $coreRegistry,
        ProductRepositoryInterface $productRepository,
        ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository,
        ImageHelper $imageHelper,
        Status $status,
        AttributeSetRepositoryInterface $attributeSetRepository,
        $scopeName = ''
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->registry = $coreRegistry;
        $this->productRepository = $productRepository;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->imageHelper = $imageHelper;
        $this->status = $status;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->scopeName = $scopeName ? $scopeName : self::DEFAULT_SCOPE_NAME;

    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $frequency = $this->registry->registry('tnw_subscriptions_billingfrequency');

        if ($frequency && $frequency->getId()){

            $productFrequencies = $this->productBillingFrequencyRepository->getListByFrequencyId(
                $frequency->getId()
            );

            $data[$frequency->getId()]['links'][self::DATA_SCOPE_LINKED_PRODUCTS] = [];

            /** @var  ProductBillingFrequencyInterface $productFrequency */
            foreach ($productFrequencies->getItems() as $productFrequency){
                $product = $this->productRepository->getById($productFrequency->getMagentoProductId());

                if ($product && $product->getId()){
                    $data[$frequency->getId()]['links'][self::DATA_SCOPE_LINKED_PRODUCTS][] = $this->fillData($product, $productFrequency);
                }
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $content = __(
            'Merchants are able to configure recurring options for this iteration for all linked products in the products administrative area.'
        );

        $meta = array_merge_recursive(
            $meta,
            [
                static::GROUP_LINKED_PRODUCTS => [
                    'children' => [
                        'button_set' => $this->getButtonSet(
                            $content,
                            __('Manage Linked Products'),
                            static::DATA_SCOPE_LINKED_PRODUCTS
                        ),
                        'modal' => $this->getGenericModal(
                            __('Manage Linked Products'),
                            static::DATA_SCOPE_LINKED_PRODUCTS
                        ),
                        static::DATA_SCOPE_LINKED_PRODUCTS => $this->getGrid(static::DATA_SCOPE_LINKED_PRODUCTS),
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'additionalClasses' => 'admin__fieldset-section',
                                'label' => __('Linked Products'),
                                'collapsible' => false,
                                'componentType' => Fieldset::NAME,
                                'dataScope' => '',
                                'sortOrder' => 30,
                            ],
                        ],
                    ]
                ]
            ]

        );

        return $meta;
    }

    /**
     * Retrieve button set
     *
     * @param Phrase $content
     * @param Phrase $buttonTitle
     * @param string $scope
     * @return array
     */
    protected function getButtonSet(Phrase $content, Phrase $buttonTitle, $scope)
    {
        $modalTarget = $this->scopeName . '.' . $scope . '.modal';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => 'container',
                        'componentType' => 'container',
                        'label' => false,
                        'content' => $content,
                        'template' => 'ui/form/components/complex',
                    ],
                ],
            ],
            'children' => [
                'button_' . $scope => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/form/components/button',
                                'actions' => [
                                    [
                                        'targetName' => $modalTarget,
                                        'actionName' => 'toggleModal',
                                    ],
                                    [
                                        'targetName' => $modalTarget . '.' . $scope . '_product_listing',
                                        'actionName' => 'render',
                                    ]
                                ],
                                'title' => $buttonTitle,
                                'provider' => null,
                            ],
                        ],
                    ],

                ],
            ],
        ];
    }

    /**
     * Prepares config for modal slide-out panel
     *
     * @param Phrase $title
     * @param string $scope
     * @return array
     */
    protected function getGenericModal(Phrase $title, $scope)
    {
        $listingTarget = $scope . '_product_listing';

        $modal = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'dataScope' => '',
                        'options' => [
                            'title' => $title,
                            'buttons' => [
                                [
                                    'text' => __('Cancel'),
                                    'actions' => [
                                        'closeModal'
                                    ]
                                ],
                                [
                                    'text' => __('Add Selected Products'),
                                    'class' => 'action-primary',
                                    'actions' => [
                                        [
                                            'targetName' => 'index = ' . $listingTarget,
                                            'actionName' => 'save'
                                        ],
                                        'closeModal'
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'children' => [
                $listingTarget => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => false,
                                'componentType' => 'insertListing',
                                'dataScope' => $listingTarget,
                                'externalProvider' => $listingTarget . '.' . $listingTarget . '_data_source',
                                'selectionsProvider' => $listingTarget . '.' . $listingTarget . '.product_columns.ids',
                                'ns' => $listingTarget,
                                'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => true,
                                'dataLinks' => [
                                    'imports' => false,
                                    'exports' => true
                                ],
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $modal;
    }

    /**
     * Retrieve grid
     *
     * @param string $scope
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function getGrid($scope)
    {
        $dataProvider = $scope . '_product_listing';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__field-wide',
                        'componentType' => DynamicRows::NAME,
                        'label' => null,
                        'columnsHeader' => false,
                        'columnsHeaderAfterRender' => true,
                        'renderDefaultRecord' => false,
                        'template' => 'ui/dynamic-rows/templates/grid',
                        'component' => 'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
                        'addButton' => false,
                        'recordTemplate' => 'record',
                        'dataScope' => 'links',
                        'deleteButtonLabel' => __('Remove'),
                        'dataProvider' => 'data.' . $dataProvider,
                        'map' => [
                            'id' => 'entity_id',
                            'name' => 'name',
                            'status' => 'status_text',
                            'attribute_set' => 'attribute_set_text',
                            'sku' => 'sku',
                            'price' => 'price',
                            'thumbnail' => 'thumbnail_src',
                        ],
                        'links' => [
                            'insertData' => '${ $.provider }:${ $.dataProvider }'
                        ],
                        'sortOrder' => 2,
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'container',
                                'isTemplate' => true,
                                'is_collection' => true,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'dataScope' => '',
                            ],
                        ],
                    ],
                    'children' => $this->fillMeta(),
                ],
            ],
        ];
    }

    /**
     * Retrieve meta column
     *
     * @return array
     */
    protected function fillMeta()
    {
        return [
            'id' => $this->getTextColumn('id', false, __('ID'), 0),
            'thumbnail' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => Field::NAME,
                            'formElement' => Input::NAME,
                            'elementTmpl' => 'ui/dynamic-rows/cells/thumbnail',
                            'dataType' => Text::NAME,
                            'dataScope' => 'thumbnail',
                            'fit' => true,
                            'label' => __('Thumbnail'),
                            'sortOrder' => 10,
                        ],
                    ],
                ],
            ],
            'name' => $this->getTextColumn('name', false, __('Name'), 20),
            'status' => $this->getTextColumn('status', true, __('Status'), 30),
            'attribute_set' => $this->getTextColumn('attribute_set', false,
                __('Attribute Set'), 40),
            'sku' => $this->getTextColumn('sku', true, __('SKU'), 50),
            'price' => $this->getTextColumn('price', true, __('Price'), 60),
            'actionDelete' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'additionalClasses' => 'data-grid-actions-cell',
                            'componentType' => 'actionDelete',
                            'dataType' => Text::NAME,
                            'label' => __('Actions'),
                            'sortOrder' => 70,
                            'fit' => true,
                        ],
                    ],
                ],
            ],
            'position' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'dataType' => Number::NAME,
                            'formElement' => Input::NAME,
                            'componentType' => Field::NAME,
                            'dataScope' => 'position',
                            'sortOrder' => 80,
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Retrieve text column structure
     *
     * @param string $dataScope
     * @param bool $fit
     * @param Phrase $label
     * @param int $sortOrder
     * @return array
     */
    protected function getTextColumn(
        $dataScope,
        $fit,
        Phrase $label,
        $sortOrder
    ) {
        $column = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'elementTmpl' => 'ui/dynamic-rows/cells/text',
                        'component' => 'Magento_Ui/js/form/element/text',
                        'dataType' => Text::NAME,
                        'dataScope' => $dataScope,
                        'fit' => $fit,
                        'label' => $label,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];

        return $column;
    }

    /**
     * Prepare data column
     *
     * @param ProductInterface $linkedProduct
     * @param ProductBillingFrequencyInterface $linkItem
     * @return array
     */
    protected function fillData(ProductInterface $linkedProduct, ProductBillingFrequencyInterface $linkItem)
    {
        return [
            'id' => $linkedProduct->getId(),
            'thumbnail' => $this->imageHelper->init($linkedProduct, 'product_listing_thumbnail')->getUrl(),
            'name' => $linkedProduct->getName(),
            'status' => $this->status->getOptionText($linkedProduct->getStatus()),
            'attribute_set' => $this->attributeSetRepository
                ->get($linkedProduct->getAttributeSetId())
                ->getAttributeSetName(),
            'sku' => $linkedProduct->getSku(),
            'price' => $linkedProduct->getPrice(), //TODO add logic with frequency price
            'position' => 0, //TODO remove it if you don't need it
            ProductBillingFrequencyInterface::DEFAULT_BILLING_FREQUENCY => $linkItem->getDefaultBillingFrequency(),
            ProductBillingFrequencyInterface::INITIAL_FEE => $linkItem->getInitialFee()
        ];
    }
}
