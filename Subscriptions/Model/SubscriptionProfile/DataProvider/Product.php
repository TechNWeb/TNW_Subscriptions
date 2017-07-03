<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Catalog\Helper\Image;
use Magento\Framework\Api\Filter;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Item;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\Backend\Session\Quote;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ConfigurableForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Form;


class Product extends AbstractDataProvider
{
    const GROUP_SUBSCRIPTION_PROFILE_ADD_PRODUCTS = 'tnw_subscriptionprofile_create_add_products';
    const DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS = 'tnw_subscriptionprofile_create_add_products';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_GRID = 'add_product_modal_grid';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_FORM = 'add_product_modal_form';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_CONFIGURABLE_FORM = 'add_product_modal_configurable_form';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_FORM_BUTTON = 'add_to_subscription_button';
    const DEFAULT_SCOPE_NAME = 'tnw_subscriptionprofile_create_product_listing';

    protected $scopeName = '';
    /** @var UrlInterface */
    protected $urlBuilder;
    /** @var StepPool */
    protected $stepPool;
    /** @var Quote */
    protected $session;
    /** @var Image */
    protected $imageHelper;
    /** @var Context */
    protected $context;
    /** @var BillingFrequencyRepository */
    protected $frequencyRepository;
    /** @var BillingFrequencyUnitType */
    protected $frequencyUnitType;

    /**
     * Product constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param Quote $session
     * @param Image $imageHelper
     * @param Context $context
     * @param BillingFrequencyRepository $frequencyRepository
     * @param BillingFrequencyUnitType $frequencyUnitType
     * @param array $meta
     * @param array $data
     * @param string $scopeName
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlInterface $urlBuilder,
        StepPool $stepPool,
        Quote $session,
        Image $imageHelper,
        Context $context,
        BillingFrequencyRepository $frequencyRepository,
        BillingFrequencyUnitType $frequencyUnitType,
        array $meta = [],
        array $data = [],
        $scopeName = ''
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->session = $session;
        $this->imageHelper = $imageHelper;
        $this->context = $context;
        $this->frequencyUnitType = $frequencyUnitType;
        $this->frequencyRepository = $frequencyRepository;
        $this->scopeName = $scopeName ? $scopeName : self::DEFAULT_SCOPE_NAME . '.' . self::DEFAULT_SCOPE_NAME;
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
        $items = $products = [];
        $estimatedPayment = 0;

        $subQuotes = $this->session->getSubQuotes();

        $counter = 1;
        /** @var ModelQuote $subQuote */
        foreach ($subQuotes as $subQuote) {
            $subscriptionData = null;

            if (empty($subQuote->getAllItems())) {
                continue;
            }

            /** @var Item $item */
            foreach ($subQuote->getAllItems() as $item) {
                if (!$subscriptionData) {
                    $subscriptionData = $item->getBuyRequest()->getDataByKey('subscription_data');
                }

                $imageHelper = $this->imageHelper->init(
                    $item->getProduct(),
                    'product_listing_thumbnail'
                );

                $products[] = [
                    'thumbnail_alt' => $imageHelper->getLabel(),
                    'thumbnail_src' => $imageHelper->getUrl(),
                    'qty' => '(x' . $item->getQty() . ')',
                    'name' => $item->getName(),
                    'conf_options' => [], //TODO add here configurable options
                ];
            }

            $subTotal = $subQuote->getGrandTotal();

            $estimatedPayment += ((double)$subTotal * (int)$subscriptionData['period']);

            $startDate = $this->getFormattedStartDate($subscriptionData['start_on']);

            $items[] = [
                'title' => __('Subscription') . ' #' . $counter++,
                'products' => $products,
                'frequency_description' => $this->getFrequencyDescription(
                    $subTotal,
                    $subscriptionData,
                    $startDate
                ),
                'shipping_method' => __('Selected on next step'),
            ];

            $products = [];
        }

        $estimatedPayment = $this->formatPrice($estimatedPayment);

        return [
            'totalRecords' => count($items),
            'items' => $items,
            'estimatedPayment' => $estimatedPayment
        ];
    }

    /**
     * @param $price
     * @return float
     */
    protected function formatPrice($price)
    {
        return $this->context->getPriceCurrency()->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->session->getStoreId(),
            $this->session->getCurrencyId()
        );
    }


    /**
     * @param $subscriptionData
     * @return string
     */
    protected function getFormattedStartDate($startDate)
    {
        $nowDate = new \DateTime();
        $nowDate = $nowDate->format('Y-m-d');

        if ($startDate == $nowDate){
            $startDate = __('today');
        } else {
            $startDate = $this->context->getLocaleDate()->formatDate(
                $startDate,
                \IntlDateFormatter::LONG,
                false
            );
        }

        return $startDate;
    }

    /**
     * @param float|string $totalPrice
     * @param [] $subscriptionData
     * @param string $startDate
     * @return string
     */
    protected function getFrequencyDescription($totalPrice, $subscriptionData, $startDate)
    {
        $formattedPrice = $this->formatPrice($totalPrice);

        $frequencyWithUnit = $this->getFrequencyWithUnit($subscriptionData['billing_frequency']);

        $priceWithUnit = sprintf('<span>%s / %s %s</span>. ', $formattedPrice, __('every'), $frequencyWithUnit);
        $description = sprintf(
            __('Total of %s shipment(s). Products will be shipped every %s starting %s.'),
            $subscriptionData['period'],
            $frequencyWithUnit,
            $startDate
        );

        return $priceWithUnit . $description;
    }

    /**
     * @param string|int $billingFrequencyId
     * @return string
     */
    protected function getFrequencyWithUnit($billingFrequencyId)
    {
        $billingFrequency = $this->frequencyRepository->getById(
            $billingFrequencyId
        );

        $unit = $this->frequencyUnitType->getLabelByValue($billingFrequency->getUnit());


        $result = $unit;

        if ($billingFrequency->getFrequency() > 1) {
            $result = $billingFrequency->getFrequency() . ' ' . $unit;
        }

        return strtolower($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        $meta = array_merge_recursive(
            $meta,
            $this->getMetaData()
        );

        return $meta;
    }


    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {

    }

    /**
     * @return array
     */
    protected function getMetaData()
    {
        $result = [];

        if ($this->stepPool->getCurrentStep() != StepPool::STEP_PARAM_TYPE_REVIEW) {
            $modalTarget = $this->scopeName . '.' . static::DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS . '.modal';
            $result = [
                self::GROUP_SUBSCRIPTION_PROFILE_ADD_PRODUCTS => [
                    'children' => [
                        'button_add_product' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'formElement' => 'container',
                                        'componentType' => 'container',
                                        'component' => 'TNW_Subscriptions/js/components/primary-button',
                                        'template' => 'TNW_Subscriptions/form/element/primary-button',
                                        'displayPrimary' => empty($this->session->getSubQuoteIds()),
                                        'subButtonLeft' => true,
                                        'title' => __('Add Products'),
                                        'actions' => [
                                            [
                                                'targetName' => $modalTarget,
                                                'actionName' => 'toggleModal',
                                            ],
                                            [
                                                'targetName' => $modalTarget . '.grid_container.' . self::DATA_SCOPE_ADD_PRODUCT_MODAL_GRID,
                                                'actionName' => 'render',
                                            ]
                                        ],
                                        'provider' => null,
                                    ],
                                ],
                            ],
                        ],
                        'button_modify_subscriptions' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'formElement' => 'container',
                                        'componentType' => 'container',
                                        'component' => 'TNW_Subscriptions/js/components/primary-button',
                                        'template' => 'TNW_Subscriptions/form/element/primary-button',
                                        'displayPrimary' => false,
                                        'subButtonRight' => true,
                                        'visible' => !empty($this->session->getSubQuoteIds()),
                                        'title' => __('Modify Subscription(s)'),
                                        'provider' => null,
                                    ],
                                ],
                            ],

                        ],
                        'modal' => $this->getModal(),
                        'configurableModal' => $this->getConfigurableModal(),
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'additionalClasses' => 'admin__fieldset-section subscription-profile-products-container',
                                'label' => false,
                                'collapsible' => false,
                                'componentType' => Fieldset::NAME,
                                'dataScope' => '',
                                'sortOrder' => 0,
                                'style' => 'max-width: 100%'
                            ],
                        ],
                    ]
                ]
            ];
        }
        return $result;
    }

    /**
     * @return array
     */
    protected function getModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'componentType' => 'modal',
                        'imports' => [
                            'state' => '!index=tnw_subscriptionprofile_create_add_product_modal_form:responseStatus'
                        ],
                        'options' => [
                            'title' => __('Select a product'),
                            'modalClass' => 'subscriptions-add-product-modal',
                        ]
                    ],
                ],
            ],
            'children' => [
                'form_container' => [
                    'children' => [
                        self::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM => $this->getForm()
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => null,
                                'collapsible' => false,
                                'visible' => true,
                                'opened' => true,
                                'additionalClasses' => 'subscriptions-add-product-modal-form-container',
                                'componentType' => Fieldset::NAME,
                                'sortOrder' => 1
                            ],
                        ],
                    ]
                ],
                'grid_container' => [
                    'children' => [
                        self::DATA_SCOPE_ADD_PRODUCT_MODAL_GRID => $this->getGrid(),
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => null,
                                'collapsible' => false,
                                'visible' => true,
                                'opened' => true,
                                'additionalClasses' => 'subscriptions-add-product-modal-grid-container',
                                'componentType' => Fieldset::NAME,
                                'sortOrder' => 1
                            ],
                        ],
                    ]
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getGrid()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'component' => 'Magento_Ui/js/form/components/insert-listing',
                        'realTimeLink' => true,
                        'behaviourType' => 'simple',
                        'externalFilterMode' => true,
                        'componentType' => 'container',
                        'autoRender' => false,
                        'dataScope' => 'tnw_subscriptionprofile_create_add_product_modal_listing',
                        'externalProvider' => 'tnw_subscriptionprofile_create_add_product_modal_listing.tnw_subscriptionprofile_create_add_product_modal_listing_data_source',
                        'selectionsProvider' => '${ $.ns }.${ $.ns }.tnw_subscriptionprofile_product_columns.ids',
                        'ns' => 'tnw_subscriptionprofile_create_add_product_modal_listing',
                        'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'immediateUpdateBySelection' => true,
                        'dataLinks' => ['imports' => false, 'exports' => true],
                        'formProvider' => 'ns = ${ $.namespace }, index = ' . self::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM,
                        'groupCode' => 'products_grid',
                        'groupName' => 'Products grid',
                        'groupSortOrder' => 10,
                        'loading' => false
                    ],
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getForm()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => false,
                        'label' => '',
                        'componentType' => 'container',
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'dataScope' => '',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => 'tnw_subscriptions_subscriptionprofile_create_add_product',
                                'buttons' => 1,
                                Form::FORM_DATA_KEY => Form::FORM_DATA_VALUE
                            ]
                        ),
                        'autoRender' => true,
                        'ns' => 'tnw_subscriptionprofile_create_add_product_modal_form',
                        'externalProvider' => Form::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM . '_data_source',
                        'toolbarContainer' => '${ $.parentName }',
                        'formSubmitType' => 'ajax'
                    ],
                ],
            ]
        ];
    }

    /** @return array */
    protected function getConfigurableModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'componentType' => 'modal',
                        'imports' => [
                            'state' => '!index=add_product_modal_configurable_form:responseStatus'
                        ],
                        'options' => [
                            'title' => 'Configure product',
                            'modalClass' => 'subscriptions-add-product-configurable-modal',
                        ]
                    ],
                ],
            ],
            'children' => [
                self::DATA_SCOPE_ADD_PRODUCT_MODAL_CONFIGURABLE_FORM => $this->getConfigurableForm()
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getConfigurableForm()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => true,
                        'label' => '',
                        'componentType' => 'container',
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'dataScope' => '',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => 'tnw_subscriptions_subscriptionprofile_create_add_product_configurable',
                                'buttons' => 1
                            ]
                        ),
                        'autoRender' => false,
                        'ns' => 'tnw_subscriptionprofile_create_add_product_modal_configurable_form',
                        'externalProvider' => ConfigurableForm::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM . '_data_source',
                        'toolbarContainer' => '${ $.parentName }'
                    ],
                ],
            ]
        ];
    }
}
