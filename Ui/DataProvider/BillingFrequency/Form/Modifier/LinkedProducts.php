<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Form\Modifier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Block\Adminhtml\BillingFrequency\Edit\SaveButton;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Form\Modifier\LinkedProducts\GridMetadata;

/**
 * Class LinkedProducts
 */
class LinkedProducts extends AbstractModifier
{
    const DATA_SCOPE_LINKED_PRODUCTS = 'linked';
    const GROUP_LINKED_PRODUCTS = 'linked';
    const DEFAULT_SCOPE_NAME = 'tnw_billingfrequency_form.tnw_billingfrequency_form';

    /**
     * Url builder.
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Scope name.
     *
     * @var string
     */
    private $scopeName;

    /**
     * Registry.
     *
     * @var Registry
     */
    private $registry;

    /**
     * Repository for retrieving product billing frequencies.
     *
     * @var ProductBillingFrequencyRepositoryInterface
     */
    private $productBillingFrequencyRepository;

    /**
     * Repository for retrieving products.
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Image helper.
     *
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * Product status source.
     *
     * @var Status
     */
    private $status;

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
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    private $localeCurrency;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param UrlInterface $urlBuilder
     * @param Registry $coreRegistry
     * @param ProductRepositoryInterface $productRepository
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository
     * @param ImageHelper $imageHelper
     * @param Status $status
     * @param GridMetadata $gridMetadata
     * @param Config $config
     * @param ArrayManager $arrayManager
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param string $scopeName
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Registry $coreRegistry,
        ProductRepositoryInterface $productRepository,
        ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository,
        ImageHelper $imageHelper,
        Status $status,
        GridMetadata $gridMetadata,
        Config $config,
        ArrayManager $arrayManager,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $scopeName = ''
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->registry = $coreRegistry;
        $this->productRepository = $productRepository;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->imageHelper = $imageHelper;
        $this->status = $status;
        $this->scopeName = $scopeName ? $scopeName : self::DEFAULT_SCOPE_NAME;
        $this->gridMetadata = $gridMetadata;
        $this->config = $config;
        $this->arrayManager = $arrayManager;
        $this->localeCurrency = $localeCurrency;
        $this->storeManager = $storeManager;
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
                            $content
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
     * Retrieve button set.
     *
     * @param Phrase $content
     *
     * @return array
     */
    private function getButtonSet(Phrase $content)
    {
        $modalTarget = $this->scopeName . '.' . static::DATA_SCOPE_LINKED_PRODUCTS . '.modal';

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
                'button_' . static::DATA_SCOPE_LINKED_PRODUCTS => [
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
                                        'targetName' => $modalTarget . '.' . static::DATA_SCOPE_LINKED_PRODUCTS
                                            . '_product_listing',
                                        'actionName' => 'render',
                                    ]
                                ],
                                'title' => __('Manage Linked Products'),
                                'provider' => null,
                            ],
                        ],
                    ],
                ],
                'button_save_'  . static::DATA_SCOPE_LINKED_PRODUCTS => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'TNW_Subscriptions/js/components/save-button-linked',
                                'template' => 'TNW_Subscriptions/form/element/primary-button',
                                'title' => __('%1', SaveButton::LABEL),
                                'provider' => null,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Prepares config for modal slide-out panel.
     *
     * @param Phrase $title
     * @param string $scope
     * @return array
     */
    private function getGenericModal(Phrase $title, $scope)
    {
        $listingTarget = $scope . '_product_listing';

        $modal = $this->arrayManager->set('arguments/data/config', [], [
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
        ]);

        $modal = $this->arrayManager->set("children/$listingTarget/arguments/data/config", $modal, [
            'autoRender' => false,
            'componentType' => 'insertListing',
            'dataScope' => $listingTarget,
            'externalProvider' => sprintf('%1$s.%1$s_data_source', $listingTarget),
            'selectionsProvider' => sprintf('%1$s.%1$s.product_columns.ids', $listingTarget),
            'ns' => $listingTarget,
            'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
            'realTimeLink' => true,
            'dataLinks' => [
                'imports' => false,
                'exports' => true
            ],
            'behaviourType' => 'simple',
            'externalFilterMode' => true,
        ]);

        return $modal;
    }

    /**
     * Retrieve grid.
     *
     * @param string $scope
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getGrid($scope)
    {
        $dataProvider = $scope . '_product_listing';

        $grid = $this->arrayManager->set('arguments/data/config', [], [
            'additionalClasses' => 'admin__field-wide',
            'componentType' => DynamicRows::NAME,
            'label' => null,
            'renderDefaultRecord' => false,
            'template' => 'ui/dynamic-rows/templates/grid',
            'component' => 'TNW_Subscriptions/js/components/billing-frequency-dynamic-rows-grid',
            'columnsHeader' => false,
            'columnsHeaderAfterRender' => true,
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
                'reg_price' => 'reg_price',
                'thumbnail' => 'thumbnail_src',
                'initial_fee' => 'initial_fee',
                'preset_qty' => 'preset_qty',
                Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY => Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY,
                Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE => Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE,
                Attribute::SUBSCRIPTION_DISCOUNT_TYPE => Attribute::SUBSCRIPTION_DISCOUNT_TYPE,
                Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT => Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT,
                Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT => Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT,
            ],
            'links' => [
                'insertData' => '${ $.provider }:${ $.dataProvider }',
                '__disableTmpl' => [
                    'insertData' => false
                ]
            ],
            'sortOrder' => 2,
        ]);

        $grid = $this->arrayManager->set('children/record/arguments/data/config', $grid, [
            'componentType' => 'container',
            'isTemplate' => true,
            'is_collection' => true,
            'component' => 'Magento_Ui/js/dynamic-rows/record',
            'dataScope' => '',
        ]);

        return $this->arrayManager->set('children/record/children', $grid, $this->gridMetadata->fillMeta());
    }

    /**
     * Prepare data column.
     *
     * @param ProductInterface $linkedProduct
     * @param ProductBillingFrequencyInterface $linkItem
     * @return array
     * @throws \Zend_Currency_Exception
     */
    private function fillData(ProductInterface $linkedProduct, ProductBillingFrequencyInterface $linkItem)
    {
        $store = $this->storeManager->getStore(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
        $currency = $this->localeCurrency->getCurrency($store->getBaseCurrencyCode());

        $subscriptionUnlockPresetQty = $linkedProduct->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
        $presetQty = $linkItem->getPresetQty();
        if (!(int)$subscriptionUnlockPresetQty && !(int)$presetQty) {
            $presetQty = null;
        }

        return [
            'id' => $linkedProduct->getId(),
            'thumbnail' => $this->imageHelper->init($linkedProduct, 'product_listing_thumbnail')->getUrl(),
            'name' => $linkedProduct->getName(),
            'status' => $this->status->getOptionText($linkedProduct->getStatus()),
            'sku' => $linkedProduct->getSku(),
            'price' => $this->getPrice($linkedProduct, $linkItem),
            'reg_price' => $linkedProduct->getPrice()
                ? $currency->toCurrency(sprintf('%f', $linkedProduct->getPrice()))
                : '',
            ProductBillingFrequencyInterface::INITIAL_FEE =>
                $linkItem->getInitialFee(),
            ProductBillingFrequencyInterface::PRESET_QTY =>
                $presetQty,
            Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY =>
                $subscriptionUnlockPresetQty,
            Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE =>
                $linkedProduct->getData(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE),
            Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT =>
                $linkedProduct->getData(Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT),
            Attribute::SUBSCRIPTION_DISCOUNT_TYPE =>
                $linkedProduct->getData(Attribute::SUBSCRIPTION_DISCOUNT_TYPE),
            Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT =>
                $linkedProduct->getData(Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT),
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
        $lockProductPriceStatus = $this->config->getLockProductPriceStatus();

        if ($lockProductPriceStatus) {
            $price = $linkedProduct->getPrice();
        } else {
            $price = $linkItem->getPrice();
        }

        return $price;
    }
}
