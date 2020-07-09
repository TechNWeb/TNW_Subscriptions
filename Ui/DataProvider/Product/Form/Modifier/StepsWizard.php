<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Service\Serializer;

/**
* Customize tnw attributes to use steps wizard component.
*/
class StepsWizard extends BaseModifier
{
    /**
     * Name of container for subscription attributes.
     *
     * @var string
     */
    protected $settingsContainerName = 'tnw_recurring_options';

    /**
     * Subscription options wizard name
     * @var string
     */
    protected $stepWizardName = 'product_form.product_form.subscription-options.management_modal.step-wizard';

    /**
     * Recurring options container name
     * @var string
     */
    protected $recurringContainerName = 'tnw_recurring_frequencies';

    /**
     * Recurring summary container name
     * @var string
     */
    protected $summaryContainerName = 'tnw_recurring_summary';

    /**
     * Recurring inheritance container name
     * @var string
     */
    protected $inheritanceContainerName = 'tnw_recurring_inheritance';

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var BillingFrequencyRepository
     */
    private $billingFrequencyRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var string
     */
    private $scopeLabel;
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param UrlInterface $urlBuilder
     * @param BillingFrequencyRepository $billingFrequencyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreManagerInterface $storeManager
     * @param Serializer $serializer
     * @param Context $context
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        UrlInterface $urlBuilder,
        BillingFrequencyRepository $billingFrequencyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        Serializer $serializer,
        Context $context
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->urlBuilder = $urlBuilder;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->serializer = $serializer;
        parent::__construct($storeManager, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $purchaseTypePath = $this->arrayManager->findPath(
            Attribute::SUBSCRIPTION_PURCHASE_TYPE,
            $meta,
            null,
            'children'
        );
        $meta = $this->arrayManager->merge(
            $purchaseTypePath . '/arguments/data/config', $meta, [
                'component' => 'TNW_Subscriptions/js/components/purchase-type',
                'componentType' => Field::NAME,
                'formElement' => Select::NAME,
                'imports' => [
                    'frequencyRecords' => 'index = ' . $this->summaryContainerName . ':frequencyRecords'
                ]
            ]
        );
        $rootPath = $this->arrayManager->slicePath($purchaseTypePath, 0, 2);
        $rootArray = $this->arrayManager->get($rootPath, $meta);
        $inheritancePath = $this->arrayManager->findPath(
            Attribute::SUBSCRIPTION_INHERITANCE,
            $meta,
            null,
            null
        );
        $this->setScopeLabel($this->arrayManager->get(
            $inheritancePath . '/arguments/data/config/scopeLabel', $meta
        ));
        // Move all fields to container
        if (!empty($rootArray)) {
            $settingsChildren = [];
            $recurringChildren = [];
            foreach ($rootArray as $key => $value) {
                if ($key == 'container_' . Attribute::SUBSCRIPTION_PURCHASE_TYPE) {
                    $meta = $this->arrayManager->set(
                        $rootPath . '/' . $key . '/arguments/data/config/sortOrder', $meta, 20
                    );
                } elseif ($key == 'container_recurring_options') {
                    $recurringChildren[$key] = $value;
                    $meta = $this->arrayManager->remove($rootPath . '/' . $key, $meta);
                } elseif ($key == 'container_' . Attribute::SUBSCRIPTION_INHERITANCE) {
                    $meta = $this->arrayManager->remove($rootPath . '/' . $key, $meta);
                } else {
                    $settingsChildren[$key] = $value;
                    $meta = $this->arrayManager->remove($rootPath . '/' . $key, $meta);
                }
            }

            $updatedMeta = [
                'top_text' => $this->getRecurringOptionsTopText(),
                'recurring_summary' => $this->getSummaryContainer(),
                'management_modal' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => Modal::NAME,
                                'component' => 'Magento_Ui/js/modal/modal-component',
                                'options' => [
                                    'title' => __('Recurring Options Management'),
                                    'clickableOverlay' => false
                                ]
                            ]
                        ]
                    ],
                    'children' => [
                        'step-wizard' => [
                            'arguments' => $this->getStepWizardConfig(),
                            'children' => [
                                $this->settingsContainerName => [
                                    'children' => $settingsChildren,
                                    'arguments' => $this->getSettingsContainerConfig()
                                ],
                                $this->recurringContainerName => [
                                    'children' => $recurringChildren,
                                    'arguments' => $this->getRecurringContainerConfig()
                                ],
                                $this->summaryContainerName => $this->getSummaryContainer()
                            ]
                        ]
                    ]
                ]
            ];

            if ($this->locator->getProduct()->getTypeId() === Configurable::TYPE_CODE) {
                $updatedMeta['management_modal']['children']['step-wizard']['children']
                [$this->inheritanceContainerName] = $this->getInheritanceContainer();
            }

            $meta = $this->arrayManager->merge($rootPath, $meta, $updatedMeta);
        }

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $inheritAttr =& $data[$this->locator->getProduct()->getId()]['product']['tnw_subscr_inheritance'];

        if ($inheritAttr) {
            $inheritAttr = $this->serializer->unserialize($inheritAttr);
        } else {
            $inheritAttr = '';
        }
        return $data;
    }

    /**
     * Get step wizard component configuration
     * @return array
     */
    private function getStepWizardConfig()
    {
        $result = [
            'data' => [
                'config' => [
                    'componentType' => Container::NAME,
                    'component' => 'TNW_Subscriptions/js/components/subscr-step-wizard',
                    'template' => 'TNW_Subscriptions/form/element/subscr-step-wizard',
                    'modalClass' => 'subscription-options-wizard',
                    'stepsNames' => [
                        $this->stepWizardName . '.' . $this->settingsContainerName,
                        $this->stepWizardName . '.' . $this->recurringContainerName,
                        $this->stepWizardName . '.' . $this->summaryContainerName
                    ]
                ]
            ]
        ];
        if ($this->locator->getProduct()->getTypeId() === Configurable::TYPE_CODE) {
            array_unshift($result['data']['config']['stepsNames'],
                $this->stepWizardName . '.' . $this->inheritanceContainerName);
        }

        return $result;
    }

    /**
     * Get recurring attributes fieldset component configuration
     * @return array
     */
    private function getSettingsContainerConfig()
    {
        $label = ($this->locator->getProduct()->getTypeId() === Configurable::TYPE_CODE)
            ? __('Step 2: Recurring Settings')
            : __('Step 1: Recurring Settings');
        return [
            'data' => [
                'config' => [
                    'label' => $label,
                    'formElement' => Fieldset::NAME,
                    'componentType' => Fieldset::NAME,
                    'caption' => __('Settings'),
                    'sortOrder' => 30,
                    'breakLine' => false,
                    'component' => 'TNW_Subscriptions/js/components/settings-fieldset',
                    'additionalClasses' => $this->settingsContainerName,
                    'imports' => [
                        'changedPurchaseType' => 'index = ' . Attribute::SUBSCRIPTION_PURCHASE_TYPE . ':value',
                        'changedTrialStatus' => 'index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':checked',
                        'changedOfferDiscount' => 'index = ' . Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT . ':checked',
                        'changedLockPrice' => 'index = ' . Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE . ':checked'
                    ]
                ]
            ]
        ];
    }

    /**
     * Get recurring frequencies records fieldset component configuration
     * @return array
     */
    private function getRecurringContainerConfig()
    {
        $label = ($this->locator->getProduct()->getTypeId() === Configurable::TYPE_CODE)
            ? __('Step 3: Billing Frequencies')
            : __('Step 2: Billing Frequencies');
        return [
            'data' => [
                'config' => [
                    'label' => $label,
                    'formElement' => Fieldset::NAME,
                    'componentType' => Fieldset::NAME,
                    'caption' => __('Billing Frequencies'),
                    'sortOrder' => 40,
                    'breakLine' => false,
                    'component' => 'TNW_Subscriptions/js/components/recurring-options',
                    'additionalClasses' => $this->recurringContainerName
                ]
            ]
        ];
    }

    private function getInheritanceContainer()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Step 1: Select attributes'),
                        'formElement' => Fieldset::NAME,
                        'componentType' => Fieldset::NAME,
                        'caption' => __('Select attributes'),
                        'nextLabelText' => __('Next'),
                        'sortOrder' => 10,
                        'breakLine' => false,
                        'component' => 'TNW_Subscriptions/js/components/inheritance-fieldset',
                        'additionalClasses' => $this->inheritanceContainerName,
                        'imports' => []
                    ]
                ]
            ],
            'children' => [
                'inheritance_description' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => Container::NAME,
                                'formElement' => Container::NAME,
                                'component' => 'Magento_Ui/js/form/components/html',
                                'label' => null,
                                'content' => __('<p>Recurring options for a configurable product can be setup to ' .
                                    'support two different use cases.</p><p><b>Option 1</b> is where the ' .
                                    'configurable product itself has specific recurring option which apply to all ' .
                                    'child products.</p><p><b>Option 2</b> is where child products have their own ' .
                                    'specific recurring options.</p><p>This page will help you specify where the ' .
                                    'recurring option are inherited from: the parent or the child. By default all ' .
                                    'listed option will be inherited from the child records instead of parent.</p>' .
                                    '<br><h2>Inherit following attributes from child products</h2>')
                            ]
                        ]
                    ]
                ],
                'inherit_' . Attribute::SUBSCRIPTION_TRIAL_STATUS => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => Checkbox::NAME,
                                'componentType' => Field::NAME,
                                'component' => 'Magento_Ui/js/form/element/single-checkbox-toggle-notice',
                                'label' => __('Is Trial Offered'),
                                'valueMap' => [
                                    'false' => '0',
                                    'true' => '1'
                                ],
                                'notices' => [
                                    '1' => __('Child controls if the trial is offered for the product'),
                                    '0' => __('Parent controls if the trial is offered for the product')
                                ],
                                'exports' => [
                                    'checked' => 'index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':disabled'
                                ],
                                'dataScope' => 'tnw_subscr_inheritance.' . Attribute::SUBSCRIPTION_TRIAL_STATUS,
                                'prefer' => 'toggle',
                                'scopeLabel' => $this->scopeLabel
                            ]
                        ]
                    ]
                ],
                'inherit_' . Attribute::SUBSCRIPTION_START_DATE => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => Checkbox::NAME,
                                'componentType' => Field::NAME,
                                'component' => 'Magento_Ui/js/form/element/single-checkbox-toggle-notice',
                                'label' => __('Subscription Start Date'),
                                'valueMap' => [
                                    'false' => '0',
                                    'true' => '1'
                                ],
                                'notices' => [
                                    '1' => __('Child controls when the billing starts for the product'),
                                    '0' => __('Parent controls when the billing starts for the product')
                                ],
                                'exports' => [
                                    'checked' => 'index = ' . Attribute::SUBSCRIPTION_START_DATE . ':disabled'
                                ],
                                'dataScope' => 'tnw_subscr_inheritance.' . Attribute::SUBSCRIPTION_START_DATE,
                                'prefer' => 'toggle',
                                'scopeLabel' => $this->scopeLabel
                            ]
                        ]
                    ]
                ],
                'inherit_' . Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => Checkbox::NAME,
                                'componentType' => Field::NAME,
                                'component' => 'Magento_Ui/js/form/element/single-checkbox-toggle-notice',
                                'label' => __('Lock Product Price'),
                                'valueMap' => [
                                    'false' => '0',
                                    'true' => '1'
                                ],
                                'notices' => [
                                    '1' => __('Child controls if the product price is the same regardless of ' .
                                        'the billing frequency or custom'),
                                    '0' => __('Parent controls if the product price is the same regardless of ' .
                                        'the billing frequency or custom')
                                ],
                                'exports' => [
                                    'checked' => 'index = ' . Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE . ':disabled'
                                ],
                                'dataScope' => 'tnw_subscr_inheritance.' . Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE,
                                'prefer' => 'toggle',
                                'scopeLabel' => $this->scopeLabel
                            ]
                        ]
                    ]
                ],
                'inherit_' . Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => Checkbox::NAME,
                                'componentType' => Field::NAME,
                                'component' => 'Magento_Ui/js/form/element/single-checkbox-toggle-notice',
                                'label' => __('Offer Flat Discount'),
                                'valueMap' => [
                                    'false' => '0',
                                    'true' => '1'
                                ],
                                'notices' => [
                                    '1' => __('Child controls if the product price is discounted for recurring orders'),
                                    '0' => __('Parent controls if the product price is discounted for recurring orders')
                                ],
                                'exports' => [
                                    'checked' => 'index = ' . Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT . ':disabled'
                                ],
                                'dataScope' => 'tnw_subscr_inheritance.' . Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT,
                                'prefer' => 'toggle',
                                'scopeLabel' => $this->scopeLabel
                            ]
                        ]
                    ]
                ],
                'inherit_' . Attribute::SUBSCRIPTION_HIDE_QTY => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => Checkbox::NAME,
                                'componentType' => Field::NAME,
                                'component' => 'Magento_Ui/js/form/element/single-checkbox-toggle-notice',
                                'label' => __('Hide Qty'),
                                'valueMap' => [
                                    'false' => '0',
                                    'true' => '1'
                                ],
                                'notices' => [
                                    '1' => __('Child controls if the product quantity is visible on storefront'),
                                    '0' => __('Parent controls if the product quantity is visible on storefront')
                                ],
                                'exports' => [
                                    'checked' => 'index = ' . Attribute::SUBSCRIPTION_HIDE_QTY . ':disabled'
                                ],
                                'dataScope' => 'tnw_subscr_inheritance.' . Attribute::SUBSCRIPTION_HIDE_QTY,
                                'prefer' => 'toggle',
                                'scopeLabel' => $this->scopeLabel
                            ]
                        ]
                    ]
                ],
                'inherit_' . Attribute::SUBSCRIPTION_SAVINGS_CALCULATION => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => Checkbox::NAME,
                                'componentType' => Field::NAME,
                                'component' => 'Magento_Ui/js/form/element/single-checkbox-toggle-notice',
                                'label' => __('Savings Calculation'),
                                'valueMap' => [
                                    'false' => '0',
                                    'true' => '1'
                                ],
                                'notices' => [
                                    '1' => __('Child controls the savings calculation type'),
                                    '0' => __('Parent controls the savings calculation type')
                                ],
                                'exports' => [
                                    'checked' => 'index = ' . Attribute::SUBSCRIPTION_SAVINGS_CALCULATION . ':disabled'
                                ],
                                'dataScope' => 'tnw_subscr_inheritance.' . Attribute::SUBSCRIPTION_SAVINGS_CALCULATION,
                                'prefer' => 'toggle',
                                'scopeLabel' => $this->scopeLabel
                            ]
                        ]
                    ]
                ],
                'inherit_' . Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => Checkbox::NAME,
                                'componentType' => Field::NAME,
                                'component' => 'Magento_Ui/js/form/element/single-checkbox-toggle-notice',
                                'label' => __('Infinite Subscriptions'),
                                'valueMap' => [
                                    'false' => '0',
                                    'true' => '1'
                                ],
                                'notices' => [
                                    '1' => __('Child controls if the customer can choose when to stop recurring orders'),
                                    '0' => __('Parent controls if the customer can choose when to stop recurring orders')
                                ],
                                'exports' => [
                                    'checked' => 'index = ' . Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS . ':disabled'
                                ],
                                'dataScope' => 'tnw_subscr_inheritance.' . Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS,
                                'prefer' => 'toggle',
                                'scopeLabel' => $this->scopeLabel
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }

    /**
     * Get summary fieldset component
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getSummaryContainer()
    {
        $label = ($this->locator->getProduct()->getTypeId() === Configurable::TYPE_CODE)
            ? __('Step 4: Summary')
            : __('Step 3: Summary');
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Summary'),
                        'wizardSummaryLabel' => $label,
                        'formElement' => Fieldset::NAME,
                        'componentType' => Fieldset::NAME,
                        'caption' => __('Summary'),
                        'nextLabelText' => __('Save'),
                        'sortOrder' => 50,
                        'breakLine' => false,
                        'component' => 'TNW_Subscriptions/js/components/recurring-summary',
                        'template' => 'TNW_Subscriptions/form/element/recurring-summary',
                        'indexedFrequencies' => $this->getIndexedFrequencies(),
                        'priceFormat' => $this->getPriceFormatData(),
                        'priceSymbol' => $this->getCurrencySymbol(),
                        'additionalClasses' => $this->summaryContainerName,
                        'productType' => $this->locator->getProduct()->getTypeId(),
                        'imports' => [
                            'onChangedPurchaseType' => 'index = ' . Attribute::SUBSCRIPTION_PURCHASE_TYPE . ':value',
                            'setFrequencyRecords' => '${ $.provider }:data.product.recurring_options',
                            'originalPrice' => '${ $.provider }:data.product.price',
                            'trialStatus' => '${ $.provider }:data.product.tnw_subscr_trial_status',
                            'trialLength' => '${ $.provider }:data.product.tnw_subscr_trial_length',
                            'trialUnit' => '${ $.provider }:data.product.tnw_subscr_trial_length_unit',
                            'trialUnitOptions' => 'index = tnw_subscr_trial_length_unit:indexedOptions',
                            'trialPrice' => '${ $.provider }:data.product.tnw_subscr_trial_price',
                            'trialStart' => '${ $.provider }:data.product.tnw_subscr_trial_start_date',
                            'trialStartOptions' => 'index = tnw_subscr_trial_start_date:indexedOptions',
                            'lockPrice' => '${ $.provider }:data.product.tnw_subscr_lock_product_price',
                            'offerFlatDiscount' => '${ $.provider }:data.product.tnw_subscr_offer_flat_discount',
                            'flatDiscountType' => '${ $.provider }:data.product.tnw_subscr_discount_type',
                            'flatDiscountAmount' => '${ $.provider }:data.product.tnw_subscr_discount_amount',
                            'infiniteSubscription' => '${ $.provider }:data.product.tnw_subscr_inf_subscriptions',
                            'is_active' => '${ $.provider }:data.product.is_active',
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get description text component
     * @return array
     */
    private function getRecurringOptionsTopText()
    {
        $frequencyUrl = $this->urlBuilder->getUrl('tnw_subscriptions/billingfrequency/index');
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Container::NAME,
                        'formElement' => Container::NAME,
                        'label' => null,
                        'template' => 'ui/form/components/complex',
                        'sortOrder' => 10,
                        'content' => __('Recurring options enable your products and services for recurring purchases. A product must be linked with at least one <a href="%1">billing frequency</a>, if recurring can be purchased on a schedule.', $frequencyUrl)
                    ]
                ]
            ],
            'children' => [
                'manage_button' => $this->getManageOptionsButtonConfig()
            ]
        ];
    }

    /**
     * Get manage button configuration
     * @return array
     */
    private function getManageOptionsButtonConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'title' => __('Manage'),
                        'formElement' => Container::NAME,
                        'componentType' => Container::NAME,
                        'component' => 'TNW_Subscriptions/js/components/manage-button',
                        'sortOrder' => 15,
                        'imports' => [
                            'onChangedPurchaseType' => 'index = ' . Attribute::SUBSCRIPTION_PURCHASE_TYPE . ':value'
                        ],
                        'actions' => [
                            [
                                'targetName' => 'ns = ${ $.ns }, index = management_modal',
                                'actionName' => 'openModal'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get frequency labels indexed by their id
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getIndexedFrequencies()
    {
        $indexedFrequencies = [];
        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder->create();

        /** @var BillingFrequencyInterface $item */
        foreach ($this->billingFrequencyRepository->getList($searchCriteria)->getItems() as $item) {
            $indexedFrequencies[$item->getId()] = $item->getLabel();
        }
        return $indexedFrequencies;
    }

    /**
     * Get currency symbol
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCurrencySymbol()
    {
        return $this->storeManager->getStore()->getBaseCurrency()->getCurrencySymbol();
    }

    /**
     * @param string $scopeLabel
     */
    private function setScopeLabel($scopeLabel)
    {
        $this->scopeLabel = $scopeLabel;
    }

}
