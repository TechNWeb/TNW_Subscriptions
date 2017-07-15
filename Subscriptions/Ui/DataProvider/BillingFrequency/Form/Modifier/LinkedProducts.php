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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use \TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Form\Modifier\LinkedProducts\GridMetadata;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\Discount;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\LockPrice;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\UnlockPresetQty;
use TNW\Subscriptions\Model\Config;

/**
 * Class LinkedProducts
 */
class LinkedProducts extends AbstractModifier
{
    const DATA_SCOPE_LINKED_PRODUCTS = 'linked';
    const GROUP_LINKED_PRODUCTS = 'linked';
    const DEFAULT_SCOPE_NAME = 'tnw_billingfrequency_form.tnw_billingfrequency_form';

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var string */
    private $scopeName;

    /** @var Registry */
    private $registry;

    /** @var ProductBillingFrequencyRepositoryInterface */
    private $productBillingFrequencyRepository;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ImageHelper */
    private $imageHelper;

    /** @var Status */
    private $status;

    /** @var AttributeSetRepositoryInterface */
    private $attributeSetRepository;

    /**
     * Grid Metadata for Linked Products.
     *
     * @var GridMetadata
     */
    private $gridMetadata;

    /**
     * Subscriptions config.
     *
     * @var Config
     */
    private $config;

    /**
     * @param UrlInterface $urlBuilder
     * @param Registry $coreRegistry
     * @param ProductRepositoryInterface $productRepository
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository
     * @param ImageHelper $imageHelper
     * @param Status $status
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param GridMetadata $gridMetadata
     * @param Config $config
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
        GridMetadata $gridMetadata,
        Config $config,
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
        $this->gridMetadata = $gridMetadata;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $frequency = $this->registry->registry('tnw_subscriptions_billingfrequency');

        if ($frequency && $frequency->getId()) {
            $productFrequencies = $this->productBillingFrequencyRepository->getListByFrequencyId(
                $frequency->getId()
            );

            $data[$frequency->getId()]['links'][self::DATA_SCOPE_LINKED_PRODUCTS] = [];

            /** @var  ProductBillingFrequencyInterface $productFrequency */
            foreach ($productFrequencies->getItems() as $productFrequency) {
                $product = $this->productRepository->getById($productFrequency->getMagentoProductId());

                if ($product && $product->getId()) {
                    $data[$frequency->getId()]['links'][self::DATA_SCOPE_LINKED_PRODUCTS][]
                        = $this->fillData($product, $productFrequency);
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
                            'sku' => 'sku',
                            'price' => 'tnw_price',
                            'thumbnail' => 'thumbnail_src',
                            'initial_fee' => 'initial_fee',
                            'preset_qty' => 'preset_qty',
                            UnlockPresetQty::CODE_UNLOCK_PRESET_QTY => UnlockPresetQty::CODE_UNLOCK_PRESET_QTY,
                            LockPrice::CODE_LOCK_PRICE => LockPrice::CODE_LOCK_PRICE,
                            Discount::CODE_DISCOUNT_TYPE => Discount::CODE_DISCOUNT_TYPE,
                            Discount::CODE_DISCOUNT_AMOUNT => Discount::CODE_DISCOUNT_AMOUNT,
                            LockPrice::CODE_FLAT_DISCOUNT => LockPrice::CODE_FLAT_DISCOUNT,
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
                    'children' => $this->gridMetadata->fillMeta(),
                ],
            ],
        ];
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
            'sku' => $linkedProduct->getSku(),
            'price' => $this->getPrice($linkedProduct, $linkItem),
            ProductBillingFrequencyInterface::INITIAL_FEE => $linkItem->getInitialFee(),
            ProductBillingFrequencyInterface::PRESET_QTY => $linkItem->getPresetQty(),
            UnlockPresetQty::CODE_UNLOCK_PRESET_QTY => $linkedProduct->getData(UnlockPresetQty::CODE_UNLOCK_PRESET_QTY),
            LockPrice::CODE_LOCK_PRICE => $linkedProduct->getData(LockPrice::CODE_LOCK_PRICE),
            LockPrice::CODE_FLAT_DISCOUNT => $linkedProduct->getData(LockPrice::CODE_FLAT_DISCOUNT),
            Discount::CODE_DISCOUNT_TYPE => $linkedProduct->getData(Discount::CODE_DISCOUNT_TYPE),
            Discount::CODE_DISCOUNT_AMOUNT => $linkedProduct->getData(Discount::CODE_DISCOUNT_AMOUNT),
        ];
    }

    /**
     * Get price.
     *
     * @param ProductInterface $linkedProduct
     * @param ProductBillingFrequencyInterface $linkItem
     * @return float|null|string
     */
    private function getPrice(ProductInterface $linkedProduct, ProductBillingFrequencyInterface $linkItem)
    {
        $lockProductPriceStatus = $this->config->lockProductPriceStatus();

        if ($lockProductPriceStatus) {
            $price = $linkedProduct->getPrice();
        } else {
            $price = $linkItem->getPrice();
        }

        return $price;
    }
}
