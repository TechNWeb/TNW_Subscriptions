<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form as UiForm;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as RecurringOptionRepository;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ModifyForm;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;


/**
 * Class SummaryProductsForm
 */
class SummaryProductsForm extends ModifyForm
{
    /**#@+
     * Constants for container names.
     */
    const CONTAINER_PREFIX = 'container_';
    const CONTAINER_ITEM_PREFIX = 'container_item_';
    /**#@-*/

    /**#@+
     * Form data scope
     */
    const DATA_SCOPE_SUMMARY_PRODUCTS_FORM = 'tnw_subscriptionprofile_summary_products_form';
    /**#@-*/

    /**#@+
     *
     * Form request values
     */
    const FORM_DATA_KEY = 'modify_form_data';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    /**#@+
     * Edit button name.
     */
    const EDIT_BUTTON_NAME = 'edit_button';
    /**#@-*/

    /**
     * @var Manager
     */
    private $profileManager;

    /**
     * Product for current item.
     *
     * @var
     */
    private $currentProduct;

    /**
     * SummaryProductsForm constructor.
     *
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
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->profileManager = $profileManager;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $productRepository, $repository,
            $frequencyRepository, $request, $unitType, $priceCalculator, $storeManager, $config, $sessionQuote,
            $currencyFactory, $context, $imageHelper, $scope, $meta, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $data = [];
        foreach ($this->getObjects() as $subQuote) {
            /** @var Item $item */
            foreach ($this->getObjectItems($subQuote) as $item) {
                $product = $this->getProductFromItem($item);
                $presetQty = (int)$product->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
                $itemPrice = $presetQty
                    ? $item->getPrice() * $item->getQty()
                    : $item->getPrice();
                $term = !empty($subQuote->getTerm()) ? 1 : 0;
                $trialStartDate = $subQuote->getTrialStartDate();
                $startOn = isset($trialStartDate) ?
                    $trialStartDate : $subQuote->getStartDate();
                $data[$subQuote->getId()]['item_' . $item->getId()] = [
                    'price' => $itemPrice,
                    'billing_frequency' => $subQuote->getBillingFrequencyId(),
                    'term' => (string)$term,
                    'start_on' => (new \DateTime($startOn))->format('Y-m-d'),
                    'name' => $product->getName(),
                    'description' => $product->getData('short_description'),
                    'qty' => $item->getQty(),
                ];
            }
        }

        return $data;
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
                            'label' => __('Products'),
                            'collapsible' => false,
                            'componentType' => UiForm\Fieldset::NAME,
                            'additionalClasses' => 'subscription-container',
                            'dataScope' => '',
                            'sortOrder' => $iterator
                        ]
                    ]
                ]
            ];
        }

        return $result;
    }

    /**
     * Returns initial fee field definition.
     *
     * @return array
     */
    protected function getInitialFeeDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Initial Fee'),
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'dataScope' => 'initial_fee',
                        'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                        'additionalClasses' => 'admin__field-wide',
                        'visible' => $this->getTrialPeriod($this->currentProduct->getId()) ? true : false,
                        'previewLabel' => '%s',
                        'component' => 'TNW_Subscriptions/js/components/add-product-form-initial-fee',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'imports' => [
                            'changeValue' => '${ $.parentName}.billing_frequency:value',
                        ],
                        'modifySubscription' => false,
                        'parentForm' => $this->getCurrentFormName(),
                    ]
                ]
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
                        'dataScope' => ''
                    ],
                ],
            ],
            'children' => [
                'edit_button' => $this->getEditButton(),
                'billing_frequency' => $this->getBillingFrequencyDefinition(),
                'term' => $this->getTermDefinition(),
                'start_on' => $this->getStartOnDefinition(),
                'price' => $this->getPriceDefinition(),
            ]
        ];
    }

    /**
     * Returns term field definition.
     *
     * @return array
     */
    protected function getTermDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'dataType' => 'boolean',
                        'formElement' => UiForm\Element\Checkbox::NAME,
                        'componentType' => UiForm\Element\Checkbox::NAME,
                        'valueMap' => ['true' => '1', 'false' => '0'],
                        'default' => '0',
                        'description' => __('Until canceled'),
                        'label' => __('Term:'),
                        'dataScope' => 'term',
                        'required' => true,
                        'additionalClasses' => 'admin__field-wide',
                        'previewLabel' => __('Until canceled'),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-checkbox-term',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'showPreview' => $this->getCurrentFormName() . ':previewMode'
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns billing frequency field definition.
     *
     * @return array
     */
    protected function getBillingFrequencyDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'multiple' => false,
                    'config' => [
                        'label' => __('Billing Frequency:'),
                        'dataType' => 'boolean',
                        'formElement' => UiForm\Element\RadioSet::NAME,
                        'componentType' => UiForm\Element\RadioSet::NAME,
                        'dataScope' => 'billing_frequency',
                        'additionalClasses' => 'radio-options-one-column sub-legend admin__field-wide',
                        'additionalForGroup' => false,
                        'validation' => ['required-entry' => true],
                        'options' => $this->getProductBillingFrequenciesAsOptionArray(
                            $this->currentProduct->getId()
                        ),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-checkbox-set',
                        'template' => 'TNW_Subscriptions/form/element/template/checkbox-set-with-preview',
                        'showPreview' => $this->getCurrentFormName() . ':previewMode',
                        'imports' => [
                            'onPriceUpdate'=> '${ $.parentName}.price:value'
                        ],
                        'parentForm' => $this->getCurrentFormName(),
                        'priceFormat' => $this->getPriceFormatData(),
                        'currencySymbol' => $this->getCurrentCurrencySymbol(),
                    ]
                ]
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
                        'additionalClasses' => 'action-primary sub-button-right',
                        'subButtonRight' => true,
                        'title' => __('Save Changes'),
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
     * Returns full name of edit form.
     *
     * @param string|int $container
     * @param string|int $containerItem
     * @return string
     */
    protected function getFormFullName($container, $containerItem)
    {
        return self::DATA_SCOPE_SUMMARY_PRODUCTS_FORM . '.' . self::DATA_SCOPE_SUMMARY_PRODUCTS_FORM
            . '.' . self::CONTAINER_PREFIX . $container
            . '.' . self::CONTAINER_ITEM_PREFIX . $containerItem
            . '.form';
    }

    /**
     * Returns name of product form.
     *
     * @return string
     */
    protected function getProductFormName()
    {
        return SummaryInsertForm::PRODUCTS_INSERT_FORM;
    }

    /**
     * Returns list of objects to display.
     *
     * @return DataObject[]
     */
    protected function getObjects()
    {
        return [
            $this->getCurrentProfile()
        ];
    }

    /**
     * Returns list of object items to display.
     *
     * @param DataObject $object
     * @return mixed
     */
    protected function getObjectItems(DataObject $object)
    {
        return $object->getProducts();
    }

    /**
     * Returns product from object item.
     *
     * @param DataObject $item
     * @return mixed
     */
    protected function getProductFromItem(DataObject $item)
    {
        $this->currentProduct = $item->getMagentoProduct();
        return $this->currentProduct;
    }

    /**
     * Retrieve additional data to form.
     *
     * @param string $objectId
     * @param string $objectItemId
     * @return array
     */
    protected function getAdditionalData($objectId, $objectItemId)
    {
        return [
            'subscription_profile_id' => $objectId ,
            'objectItemId' => $objectItemId,
        ];
    }

    /**
     * Retrieve current subscription profile.
     *
     * @return null|SubscriptionProfileInterface
     */
    private function getCurrentProfile()
    {
        return $this->profileManager->loadProfileFromRequest('subscription_profile_id');
    }
}