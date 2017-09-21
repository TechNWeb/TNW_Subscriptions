<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\Component\Container;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryAddressForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryPaymentMethodForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryShippingMethodForm;

/**
 * Class SummaryInsertForm
 */
class SummaryInsertForm implements ModifierInterface
{
    const INSERT_FORM_HANDLE = 'handle';
    const INSERT_FORM_NAMESPACE = 'namespace';
    const INSERT_FORM_SORT_ORDER = 'sort_order';
    /**
     * Summary fieldset name
     */
    const SUMMARY_FIELDSET = 'summary';

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
    ];

    /**
     * Indicates form type.
     *
     * @var bool
     */
    private $formType;

    /**
     * Registry
     *
     * @var Registry
     */
    private $registry;

    /**
     * Url builder
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * AddressModifier constructor.
     *
     * @param Registry $registry
     * @param UrlInterface $urlBuilder
     * @param $formType
     */
    public function __construct(
        Registry $registry,
        UrlInterface $urlBuilder,
        $formType
    ) {
        $this->registry = $registry;
        $this->urlBuilder = $urlBuilder;
        $this->formType = $formType;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_merge_recursive(
            $meta,
            [
                static::SUMMARY_FIELDSET => [
                    'children' => [
                         $this->formType => $this->getInsertFormModifier()
                    ],
                ],
            ]
        );

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
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
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
     * Returns current subscription profile from registry
     *
     * @return SubscriptionProfile|null
     */
    private function getProfile()
    {
        return $this->registry->registry('tnw_subscription_profile');
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

}