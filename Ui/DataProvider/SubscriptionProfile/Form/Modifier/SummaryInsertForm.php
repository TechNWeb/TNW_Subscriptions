<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Container;
use TNW\Subscriptions\Model\ProfileCcUtils;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryAddressForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryPaymentMethodForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryShippingMethodForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryProductsForm;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as ProfileOrderManager;

/**
 * Class SummaryInsertForm
 */
class SummaryInsertForm extends BaseFormModifier
{
    const INSERT_FORM_HANDLE = 'handle';
    const INSERT_FORM_NAMESPACE = 'namespace';
    const INSERT_FORM_SORT_ORDER = 'sort_order';

    /**
     * Summary fieldset name
     */
    const SUMMARY_FIELDSET = 'summary';

    /**
     * Dashboard fieldset name
     */
    const DASHBOARD_FIELDSET = 'dashboard';

    /**
     * Form data key
     */
    const FORM_DATA_KEY = 'subscription_profile_id';

    /**
     * Insert form names
     */
    const SHIPPING_INFORMATION_INSERT_FORM = 'shipping_information_insert_form';
    const BILLING_INFORMATION_INSERT_FORM = 'billing_information_insert_form';
    const SHIPPING_METHODS_INSERT_FORM = 'shipping_method_insert_form';
    const PAYMENT_METHODS_INSERT_FORM = 'payment_method_insert_form';
    const PRODUCTS_INSERT_FORM = 'products_insert_form';

    /**
     * Configuration for insert form
     *
     * @var array
     */
    private static $insertFormData = [
        self::SHIPPING_INFORMATION_INSERT_FORM => [
            self::INSERT_FORM_HANDLE => 'tnw_subscriptions_subscriptionprofile_summary_shipping_address',
            self::INSERT_FORM_NAMESPACE => SummaryAddressForm::DATA_SCOPE_SUMMARY_SHIPPING_ADDRESS_FORM,
            self::INSERT_FORM_SORT_ORDER => 10,
        ],
        self::BILLING_INFORMATION_INSERT_FORM => [
            self::INSERT_FORM_HANDLE => 'tnw_subscriptions_subscriptionprofile_summary_billing_address',
            self::INSERT_FORM_NAMESPACE => SummaryAddressForm::DATA_SCOPE_SUMMARY_BILLING_ADDRESS_FORM,
            self::INSERT_FORM_SORT_ORDER => 30,
        ],
        self::SHIPPING_METHODS_INSERT_FORM => [
            self::INSERT_FORM_HANDLE => 'tnw_subscriptions_subscriptionprofile_summary_shipping_method',
            self::INSERT_FORM_NAMESPACE => SummaryShippingMethodForm::FORM_NAME,
            self::INSERT_FORM_SORT_ORDER => 20,
        ],
        self::PAYMENT_METHODS_INSERT_FORM => [
            self::INSERT_FORM_HANDLE => 'tnw_subscriptions_subscriptionprofile_summary_payment_method',
            self::INSERT_FORM_NAMESPACE => SummaryPaymentMethodForm::FORM_NAME,
            self::INSERT_FORM_SORT_ORDER => 40,
        ],
        self::PRODUCTS_INSERT_FORM => [
            self::INSERT_FORM_HANDLE => 'tnw_subscriptions_subscriptionprofile_summary_products',
            self::INSERT_FORM_NAMESPACE => SummaryProductsForm::DATA_SCOPE_MODAL_FORM,
            self::INSERT_FORM_SORT_ORDER => 50,
        ],
    ];

    /**
     * Indicates form type.
     *
     * @var bool
     */
    private $formType;

    /**
     * @var ProfileCcUtils
     */
    private $utils;

    /**
     * @var ProfileOrderManager
     */
    private $profileOrderManager;

    /**
     * @param Registry            $registry
     * @param UrlInterface        $urlBuilder
     * @param ProfileOrderManager $profileOrderManager
     * @param ProfileCcUtils      $utils
     * @param bool                $formType
     */
    public function __construct(
        Registry $registry,
        UrlInterface $urlBuilder,
        ProfileOrderManager $profileOrderManager,
        ProfileCcUtils $utils,
        $formType
    ) {
        $this->profileOrderManager = $profileOrderManager;
        $this->utils = $utils;
        $this->formType = $formType;
        parent::__construct($urlBuilder, $registry);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $result = [];
        if ($this->canShowBlock($this->formType)) {
            $result = [
                $this->getInsertLocation($this->formType) => [
                    'children' => [
                        $this->formType => $this->getInsertFormModifier()
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'tabMessages' => $this->getTabMessages(),
                            ],
                        ],
                    ],
                ],
            ];
        }


        $meta = array_merge_recursive($meta, $result);

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * Returns Insert form meta
     *
     * @return array
     */
    private function getInsertFormModifier()
    {
        $ns = $this->getNamespace();

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => true,
                        'label' => false,
                        'componentType' => Container::NAME,
                        'component' => 'TNW_Subscriptions/js/components/dashboard-insert-form',
                        'update_url' => $this->getUrlBuilder()->getUrl('mui/index/render'),
                        'render_url' => $this->getUrlBuilder()->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => $this->getHandle(),
                                self::FORM_DATA_KEY => $this->getProfileId()
                            ]
                        ),
                        'autoRender' => true,
                        'ns' => $ns,
                        'externalProvider' => $ns . '.' . $ns . '_data_source',
                        'formSubmitType' => 'ajax',
                        'sortOrder' => $this->getSortOrder(),
                    ],
                ],
            ]
        ];
    }

    /**
     * Returns sort order for address
     *
     * @return int
     */
    private function getSortOrder()
    {
        return self::$insertFormData[$this->formType][self::INSERT_FORM_SORT_ORDER];
    }

    /**
     * Returns namespace name depends on "formType" param
     *
     * @return string
     */
    private function getNamespace()
    {
        return self::$insertFormData[$this->formType][self::INSERT_FORM_NAMESPACE];
    }

    /**
     * Returns handle depends on "formType" param
     *
     * @return string
     */
    private function getHandle()
    {
        return self::$insertFormData[$this->formType][self::INSERT_FORM_HANDLE];
    }

    /**
     * Returns current subscription profile id from registry
     *
     * @return mixed|null|string
     */
    private function getProfileId()
    {
        return $this->getProfile() ? $this->getProfile()->getId() : null;
    }

    /**
     * @inheritdoc
     */
    protected function getTabMessages()
    {
        $messages = parent::getTabMessages();
        $profile = $this->getProfile();

        if ($profile && $this->utils->isCcPayment($profile)) {
            $relation = $this->getNextProfileRelation($profile);
            if (
                false !== $relation &&
                $this->utils->isCcExpireBy($profile, $relation->getScheduledAt())
            ) {
                $messages[] = __('Credit Card will expire before next billing cycle.');
            }
        }

        return $messages;
    }

    /**
     * Returns next profile relation instance.
     *
     * @param SubscriptionProfile $profile
     * @return false|\TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface
     */
    private function getNextProfileRelation(SubscriptionProfile $profile)
    {
        return $this->profileOrderManager->getNextProfileRelation($profile, false);
    }

    /**
     * Check if it necessary to show block.
     *
     * @param string $formType
     * @return bool
     */
    private function canShowBlock($formType)
    {
        $result = true;
        switch ($formType) {
            case self::SHIPPING_METHODS_INSERT_FORM:
            case self::SHIPPING_INFORMATION_INSERT_FORM:
            if ((bool)$this->getProfile()->getIsVirtual()) {
                    $result = false;
                }
                break;
            case self::BILLING_INFORMATION_INSERT_FORM:
            case self::PAYMENT_METHODS_INSERT_FORM:
            case self::PRODUCTS_INSERT_FORM:
            default:
                break;
        }

        return $result;
    }

    /**
     * Get insert location of inserted form
     * @param $formType
     * @return string
     */
    private function getInsertLocation($formType)
    {
        return $formType === self::PRODUCTS_INSERT_FORM ? self::DASHBOARD_FIELDSET : self::SUMMARY_FIELDSET;
    }
}
