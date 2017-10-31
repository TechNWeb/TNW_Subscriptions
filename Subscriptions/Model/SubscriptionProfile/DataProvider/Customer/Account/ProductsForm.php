<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Customer\Account;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as RecurringOptionRepository;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use  TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryProductsForm;
use Magento\Ui\Component\Form as UiForm;
use Magento\Ui\Component\Container as UiContainer;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use Magento\Framework\UrlInterface;

/**
 * Subscription items form data provider for customer account dashboard page.
 */
class ProductsForm extends SummaryProductsForm
{
    /**
     * Form data scope
     */
    const DATA_SCOPE_MODAL_FORM = 'tnw_subscriptionprofile_products_and_services_form';

    /**
     * Url Builder.
     *
     * @var UrlInterface
     */
    private $url;

    /**
     * @var array
     */
    protected $requestFields = [
        'billing_frequency',
        'term',
        'period',
        'start_on',
        'qty',
    ];

    /**
     * ProductsForm constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ProductRepositoryInterface $productRepository
     * @param RecurringOptionRepository $repository
     * @param BillingFrequencyRepository $frequencyRepository
     * @param RequestInterface $request
     * @param TrialLengthUnitType $unitType
     * @param PriceCalculator $priceCalculator
     * @param StoreManagerInterface $storeManager
     * @param \TNW\Subscriptions\Model\Config $config
     * @param QuoteSessionInterface $sessionQuote
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param Context $context
     * @param ImageHelper $imageHelper
     * @param Manager $profileManager
     * @param UrlInterface $url
     * @param string $scope
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ProductRepositoryInterface $productRepository,
        RecurringOptionRepository $repository,
        BillingFrequencyRepository $frequencyRepository,
        RequestInterface $request,
        TrialLengthUnitType $unitType,
        PriceCalculator $priceCalculator,
        StoreManagerInterface $storeManager,
        \TNW\Subscriptions\Model\Config $config,
        QuoteSessionInterface $sessionQuote,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        Context $context,
        ImageHelper $imageHelper,
        Manager $profileManager,
        UrlInterface $url,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->url = $url;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $productRepository, $repository,
            $frequencyRepository, $request, $unitType, $priceCalculator, $storeManager, $config, $sessionQuote,
            $currencyFactory, $context, $imageHelper, $profileManager, $scope, $meta, $data);
    }


    /**
     * @inheritdoc
     */
    protected function getCurrentProfile()
    {
        return $this->profileManager->loadProfileFromRequest('entity_id');
    }

    /**
     * @inheritdoc
     */
    protected function getAdditionalData($objectId, $objectItemId)
    {
        return [
            'entity_id' => $objectId,
            'objectItemId' => $objectItemId,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getPriceDefinition()
    {
        $label = __('Price') . ':';
        if (isset($this->currentProduct)
            && $this->getTrialPeriod($this->currentProduct->getId())) {
            $label = __('Post trial price:');
        }
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => $label,
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'dataScope' => 'price',
                        'additionalClasses' => 'field-wide',
                        'validation' => [
                            'validate-zero-or-greater' => true,
                            'required-entry' => true
                        ],
                        'addSymbol' => false,
                        'addbefore' => $this->getCurrentCurrencySymbol(),
                        'component' => 'TNW_Subscriptions/js/components/add-product-form-price',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'previewLabel' => $this->getCurrentCurrencySymbol() . '%s',
                        'imports' => [
                            'changeValue' => '${ $.parentName}.billing_frequency:value',
                        ],
                        'priceFormat' => $this->getPriceFormatData(),
                        'modifySubscription' => true,
                        'parentForm' => $this->getCurrentFormName(),
                    ]
                ]
            ]
        ];
    }


    /**
     * @inheritdoc
     */
    protected function getQtyContainerDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'component' => 'TNW_Subscriptions/js/components/group',
                        'componentType' => UiContainer::NAME,
                        'additionalForGroup' => false,
                        'fieldTemplate' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'additionalClasses' => 'qty-container'
                    ]
                ]
            ],
            'children' => [
                'qty' => $this->getQtyDefinition()
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getLeftContainerDefinition()
    {
        $imageHelper = $this->getImageHelper();

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'collapsible' => false,
                        'componentType' => UiForm\Fieldset::NAME,
                        'additionalClasses' => 'left-container',
                        'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                    ]
                ]
            ],
            'children' => [
                'image' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => UiForm\Element\Input::NAME,
                                'formElement' => UiForm\Element\Input::NAME,
                                'elementTmpl' => 'TNW_Subscriptions/form/element/image',
                                'additionalClasses' => 'sub-product-image',
                                'src' => isset($this->currentProduct) ? $imageHelper->getUrl()
                                    : $imageHelper->getDefaultPlaceholderUrl('small_image')
                            ]
                        ]
                    ]
                ],
                'remove_button' => $this->getRemoveButton(),
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getFormEditButtons()
    {
        return [
            'form_button' => $this->getCurrentFormName() . '.edit_fieldset.edit_button',
        ];
    }

    /**
     * Returns meta data.
     *
     * @return array
     */
    protected function getMetaData()
    {
        $iterator = 0;
        $result = [];
        foreach ($this->getObjects() as $subQuote) {
            $iterator++;
            $result[self::CONTAINER_PREFIX . $subQuote->getId()] = [
                'children' => $this->getChildren($subQuote),
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => false,
                            'collapsible' => false,
                            'componentType' => UiForm\Fieldset::NAME,
                            'additionalClasses' => 'subscription-container',
                            'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                            'dataScope' => '',
                            'sortOrder' => $iterator
                        ]
                    ]
                ]
            ];
        }

        return $result;
    }
}
