<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as RecurringOptionRepository;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\Trial;


class Form extends AbstractDataProvider
{
    const GROUP_ADD_PRODUCT_MODAL_FORM = 'tnw_subscriptionprofile_create_add_product_modal_form';

    const FORM_DATA_KEY = 'add_product_modal_form_data';
    const FORM_DATA_VALUE = 'new_subscription';

    const DEFAULT_PERIOD_VALUE = 1;

    const DATA_SCOPE_ADD_PRODUCT_MODAL_FORM = 'tnw_subscriptionprofile_create_add_product_modal_form.tnw_subscriptionprofile_create_add_product_modal_form';

    protected $scopeName;

    /** @var [] */
    protected $loadedData;
    /** @var UrlInterface */
    protected $urlBuilder;
    /** @var StepPool */
    protected $stepPool;
    /** @var Registry */
    protected $registry;
    /** @var ProductRepositoryInterface */
    protected $productRepository;
    /** @var RecurringOptionRepository */
    protected $recurringOptionRepository;
    /** @var RequestInterface */
    protected $request;
    /** @var BillingFrequencyRepository */
    protected $frequencyRepository;

    protected $productBillingFrequencies;

    /**
     * Form constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param Registry $registry
     * @param ProductRepositoryInterface $productRepository
     * @param RecurringOptionRepository $repository
     * @param BillingFrequencyRepository $frequencyRepository
     * @param RequestInterface $request
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
        ProductRepositoryInterface $productRepository,
        RecurringOptionRepository $repository,
        BillingFrequencyRepository $frequencyRepository,
        RequestInterface $request,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->registry = $registry;
        $this->productRepository = $productRepository;
        $this->recurringOptionRepository = $repository;
        $this->frequencyRepository = $frequencyRepository;
        $this->request = $request;
        $this->scopeName = $scope ? $scope : self::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM;
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

        /** @var ProductBillingFrequencyInterface $frequency */
        foreach ($this->getProductBillingFrequencies() as $frequency) {

            if ($frequency->getDefaultBillingFrequency()) {
                $data[self::FORM_DATA_VALUE]['product_billing_frequency'] = $frequency->getBillingFrequencyId();
            }
            $data[self::FORM_DATA_VALUE]['price'] = $frequency->getPrice();
            $data[self::FORM_DATA_VALUE]['product_frequencies'][$frequency->getBillingFrequencyId()] = $frequency->getPrice();
        }

        $data[self::FORM_DATA_VALUE]['period'] = self::DEFAULT_PERIOD_VALUE;

        return $data;
    }

    /**
     * @return mixed
     */
    public function getConfigData()
    {
        $confData = parent::getConfigData();

        $confData = array_merge($confData, $this->getAdditionalConfig());

        return $confData;
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        $meta = array_merge_recursive(
            $meta,
            $this->getButtonsMetaData(),
            $this->getFieldsMetaData()
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
    protected function getFieldsMetaData()
    {
        return $result = [
            'billing_frequency' => [
                'children' => [
                    'product_billing_frequency' => [
                        'arguments' => [
                            'data' => [
                                'options' => $this->getProductBillingFrequenciesAsOptionArray()
                            ]

                        ]
                    ],
                    'period' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'imports' => [
                                        'visible' => '!ns = ${ $.ns }, index = term:checked',
                                    ]
                                ]
                            ]

                        ]
                    ],
                    'start_on' => [
                        'arguments' => [
                            'data' => [
                                'config' => $this->getStartOnFieldConfig()
                            ]
                        ]
                    ],
                    'price' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'component' => 'TNW_Subscriptions/js/components/add-product-form-price',
                                    'validation' => [
                                        'validate-zero-or-greater' => true
                                    ],
                                    'imports' => [
                                        'changeValue' => 'index = product_billing_frequency:value',
                                    ]
                                ],
                            ],
                        ],
                    ]
                ]
            ]
        ];
    }


    /**
     * @return array
     */
    protected function getButtonsMetaData()
    {
        return $result = [
            'add_to_subscription' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'container',
                            'componentType' => 'container',
                            'component' => 'Magento_Ui/js/form/components/button',
                            'title' => 'Add to Subscription',
                            'actions' => [
                                [
                                    'targetName' => $this->scopeName,
                                    'actionName' => 'save'
                                ]
                            ],
                            'provider' => null
                        ],
                    ],
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getAdditionalConfig()
    {
        return [
            'subProductListing' => Product::DEFAULT_SCOPE_NAME,
            'insertForm' => Product::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM,
            'configurableModal' => 'configurableModal',
            'mainModal' => 'modal',
            'insertConfigurableForm' => Product::DATA_SCOPE_ADD_PRODUCT_MODAL_CONFIGURABLE_FORM,
            'configurableForm' => ConfigurableForm::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM,
            'modalGrid' => Product::DATA_SCOPE_ADD_PRODUCT_MODAL_GRID
        ];
    }

    protected function getStartOnFieldConfig()
    {
        $visible = false;
        $value = null;

        $productId = $this->request->getParam('product_id', null);

        if ($productId) {
            /** @var MagentoProduct $product */
            $product = $this->productRepository->getById($productId);

            if ($product->getData(Trial::CODE_TRIAL)) {
                if ($product->getData(Trial::CODE_TRIAL_START_DATE) == StartDateType::DEFINED_BY_CUSTOMER) {
                    $visible = true;
                } else {
                    $value = $product->getData(Trial::CODE_TRIAL_START_DATE);
                }
            } else {
                if ($product->getData(Trial::CODE_START_DATE) == StartDateType::DEFINED_BY_CUSTOMER) {
                    $visible = true;
                } else {
                    $value = $product->getData(Trial::CODE_START_DATE);
                }
            }
        }

        return [
            'visible' => $visible,
            'value' => $value
        ];
    }

    /**
     * @return array
     */
    protected function getProductBillingFrequencies()
    {
        if (!is_array($this->productBillingFrequencies)) {

            $this->productBillingFrequencies = [];

            $productId = $this->request->getParam('product_id', null);

            if ($productId) {
                $this->productBillingFrequencies = $this->recurringOptionRepository
                    ->getListByProductId($productId)
                    ->getItems();
            }
        }

        return $this->productBillingFrequencies;
    }

    /**
     * @return array
     */
    public function getProductBillingFrequenciesAsOptionArray()
    {
        $result = [];

        /** @var ProductBillingFrequencyInterface $productFrequency */
        foreach ($this->getProductBillingFrequencies() as $productFrequency) {
            $frequency = $this->frequencyRepository->getById($productFrequency->getBillingFrequencyId());

            $result[] = [
                'label' => $frequency->getLabel(),
                'value' => $productFrequency->getBillingFrequencyId()
            ];
        }

        return $result;
    }
}
