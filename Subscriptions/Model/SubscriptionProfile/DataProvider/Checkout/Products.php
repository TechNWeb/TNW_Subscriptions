<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout;

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
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ModifyForm;
use Magento\Ui\Component\Form as UiForm;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator;
use Magento\Ui\Component\Container as UiContainer;


class Products extends ModifyForm
{
    /**#@+
     * Insert form data scope
     * //TODO change constant value to 'insert_form_content' after merge
     */
    const DATA_SCOPE_INSERT_FORM = 'insert_form';
    /**#@-*/

    /**#@+
     * Form data scope
     */
    const DATA_SCOPE_MODAL_FORM = 'tnw_subscriptionprofile_checkout_products_form';
    /**#@-*/

    /**
     * Description creator.
     *
     * @var DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * Products constructor.
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
     * @param DescriptionCreator $descriptionCreator
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
        DescriptionCreator $descriptionCreator,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->descriptionCreator = $descriptionCreator;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $productRepository, $repository,
            $frequencyRepository, $request, $unitType, $priceCalculator, $storeManager, $config, $sessionQuote,
            $currencyFactory, $context, $imageHelper, $scope, $meta, $data);
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
                                'src' => $imageHelper->getUrl()
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
    protected function getPriceDefinition()
    {
        return $this->getTextFieldDefenition('price');
    }

    /**
     * @inheritdoc
     */
    protected function getMiddleContainerDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'collapsible' => false,
                        'componentType' => UiForm\Fieldset::NAME,
                        'additionalClasses' => 'middle-container',
                        'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                    ],
                ],
            ],
            'children' => [
                'name' => $this->getTextFieldDefenition('name'),
                'description' => $this->getTextFieldDefenition('description'),
                'qty_container' => $this->getQtyContainerDefinition(),
                'price' => $this->getPriceDefinition(),
                'update_button' => $this->getUpdateButton()
            ]
        ];
    }

    /**
     * Returns edit fieldset definition.
     *
     * @return array
     */
    protected function getEditFieldsetDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'collapsible' => false,
                        'componentType' => UiForm\Fieldset::NAME,
                        'additionalClasses' => 'edit-fieldset',
                        'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                        'dataScope' => ''
                    ],
                ],
            ],
            'children' => [
                'billing_frequency' => $this->getBillingFrequencyDefinition(),
                'term' => $this->getTermDefinition(),
                'period' => $this->getPeriodDefenition(),
                'start_on' => $this->getStartOnDefinition(),
                'trial_period' => $this->getTrialPeriodDefenition(),
                'initial_fee' => $this->getInitialFeeDefinition(),
                'price' => $this->getPriceDefinition(),
                'update_button' => $this->getUpdateButton()
            ]
        ];
    }

    /**
     * Returns update button definition.
     *
     * @return array
     */
    protected function getUpdateButton()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => UiContainer::NAME,
                        'componentType' => UiContainer::NAME,
                        'component' => 'TNW_Subscriptions/js/components/edit-button',
                        'additionalClasses' => 'action-primary action primary sub-button-right',
                        'elementTmpl' => 'TNW_Subscriptions/form/element/update-button',
                        'subButtonRight' => true,
                        'title' => __('Update'),
                        'actions' => [
                            [
                                'targetName' => $this->getCurrentFormName(),
                                'actionName' => 'save',
                            ],
                        ],
                        'provider' => null,
                        'imports' => [
                            'visible' => '!' . $this->getCurrentFormName() . ':buttonPreviewMode'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param $presetQty
     * @param float|string $price
     * @param Item $item
     * @return string
     */
    protected function getItemPrice($presetQty, $price, $item)
    {
        $subBuyRequest = $item->getBuyRequest()->getDataByPath(Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);
        $initialFee = $this->getInitialFeeFromItem($item);
        return $this->descriptionCreator->getDescribedItemPriceHtml($item->getRowTotal(), $subBuyRequest, $initialFee);
    }

    protected function getFormEditButtons()
    {
        return [
            'form_button' => $this->getCurrentFormName() . '.edit_button',
            'qty_button' => $this->getCurrentFormName() . '.description_fieldset.middle_container.qty_container.qty_edit_button'
        ];
    }

    /**
     * Return item edit form definition.
     *
     * @param string|int $objectId
     * @param string|int $itemId
     * @return array
     */
    protected function getForm($objectId, $itemId)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => UiForm::NAME,
                        'componentType' => UiForm::NAME,
                        'component' => 'TNW_Subscriptions/js/components/modify-products-form',
                        'additionalData' => $this->getAdditionalData($objectId, $itemId),
                        'productsFormName' => $this->getProductFormName(),
                        'editButtons' => $this->getFormEditButtons()
                    ]
                ]
            ],
            'children' => [
                'description_fieldset' => $this->getDescriptionFieldset(),
                'edit_fieldset' => $this->getEditFieldsetDefinition(),
                'edit_button' => $this->getEditButton(),
            ]
        ];
    }

    /**
     * Returns name of product form.
     *
     * @return string
     */
    protected function getProductFormName()
    {
        return self::DATA_SCOPE_INSERT_FORM;
    }
}