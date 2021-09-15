<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\Item;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form as UiForm;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context as FormContext;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ModifyForm;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
/**
 * DataProvider to show products on subscription checkout.
 */
class Products extends ModifyForm
{
    /**#@+
     * Insert form data scope
     */
    const DATA_SCOPE_INSERT_FORM = 'insert_form_content';
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
     * @var array
     */
    protected $requestFields = [
        'billing_frequency',
        'term',
        'period',
        'start_on',
        'qty',
        'super_attribute',
    ];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param PriceCalculator $priceCalculator
     * @param Context $context
     * @param FormContext $formContext
     * @param PoolInterface $pool
     * @param DescriptionCreator $descriptionCreator
     * @param Registry $registry
     * @param ProductTypeManagerResolver $productTypeResolver
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param StockRegistryInterface $stockRegistry
     * @param string $scope
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        PriceCalculator $priceCalculator,
        Context $context,
        FormContext $formContext,
        PoolInterface $pool,
        DescriptionCreator $descriptionCreator,
        Registry $registry,
        ProductTypeManagerResolver $productTypeResolver,
        SubscriptionProfileRepositoryInterface $profileRepository,
        StockRegistryInterface $stockRegistry,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->descriptionCreator = $descriptionCreator;
        $this->stockRegistry = $stockRegistry;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $priceCalculator,
            $context,
            $formContext,
            $pool,
            $registry,
            $productTypeResolver,
            $stockRegistry,
            $profileRepository,
            $scope,
            $meta,
            $data
        );
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
                'remove_button' => $this->getRemoveButton(),
                'description' => $this->getTextFieldDefenition('description'),
                'qty_container' => $this->getQtyContainerDefinition(),
                'price' => $this->getPriceDefinition(),
                'update_button' => $this->getUpdateButton()
            ]
        ];
    }

    /**
     * Returns edit fieldset definition
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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

    /**
     * Returns edit form button names.
     *
     * @return array
     */
    protected function getFormEditButtons()
    {
        $result = [
            'form_button' => $this->getCurrentFormName() . '.edit_button',
        ];
        if ($this->currentProduct && !$this->currentProduct->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY)) {
            $result['qty_button'] = $this->getCurrentFormName()
                . '.description_fieldset.middle_container.qty_container.qty_edit_button';
        }

        return $result;
    }

    /**
     * Return item edit form definition.
     *
     * @param int|string $objectId
     * @param int|string $itemId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
                        'isSubscriptionsVirtual' => $this->getIsVirtualFromSubscriptions(),
                        'requestFields' => $this->getRequestFields(),
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

    /**
     * @inheritdoc
     */
    protected function getBillingFrequencyData(
        DataObject $productDataObject,
        ProductBillingFrequencyInterface $frequency
    ) {
        $data = parent::getBillingFrequencyData($productDataObject, $frequency);
        $billingFrequencyId = $frequency->getBillingFrequencyId();
        $data['is_trial'] = $productDataObject->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS) ?: 0;

        $data['no_format_initial_fee'] = $this->getNotFormattedInitialFee(
            $billingFrequencyId,
            $productDataObject->getChildProductId()
        );
        $data['trial_price'] = $productDataObject->getData(Attribute::SUBSCRIPTION_TRIAL_PRICE) ?: 0;
        $data['frequency_unit_string'] = $this->descriptionCreator->getFrequencyWithUnit($billingFrequencyId);

        return $data;
    }

    /**
     * Returns is virtual param from all subscriptions.
     *
     * @return bool
     */
    private function getIsVirtualFromSubscriptions()
    {
        $result = true;
        foreach ($this->formContext->getSession()->getSubQuotes() as $quote) {
            if (!$quote->isVirtual()) {
                $result = false;
                break;
            }
        }

        return $result;
    }
}
