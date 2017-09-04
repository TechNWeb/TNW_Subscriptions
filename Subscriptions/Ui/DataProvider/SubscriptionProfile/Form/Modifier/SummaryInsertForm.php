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
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryForm;

/**
 * Class AddressInfo
 */
class SummaryInsertForm implements ModifierInterface
{
    const SUMMARY_FIELDSET = 'summary';

    const FORM_DATA_KEY = 'subscription_profile_id';

    const SHIPPING_INFORMATION_INSERT_FORM = 'shipping_information_insert_form';
    const BILLING_INFORMATION_INSERT_FORM = 'billing_information_insert_form';

    const SHIPPING_INFORMATION_FORM_HANDLE = 'tnw_subscriptions_subscriptionprofile_summary_address';
    const BILLING_INFORMATION_FORM_HANDLE = 'tnw_subscriptions_subscriptionprofile_summary_address';

    /**
     * Flag that indicates that this is a shipping address form.
     *
     * @var bool
     */
    private $isShipping;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * AddressModifier constructor.
     *
     * @param Registry $registry
     * @param UrlInterface $urlBuilder
     * @param $isShipping
     */
    public function __construct(
        Registry $registry,
        UrlInterface $urlBuilder,
        $isShipping
    ) {
        $this->registry = $registry;
        $this->urlBuilder = $urlBuilder;
        $this->isShipping = $isShipping;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $addressInfoInsertFormName = $this->getAddressInfoInsertFormName();

        $meta = array_merge_recursive(
            $meta,
            [
                static::SUMMARY_FIELDSET => [
                    'children' => [
                        $addressInfoInsertFormName => $this->getInsertFormModifier()
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
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => true,
                        'label' => 'FORM',
                        'componentType' => Container::NAME,
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => self::SHIPPING_INFORMATION_FORM_HANDLE,
                                self::FORM_DATA_KEY => $this->getProfileId()
                            ]
                        ),
                        'autoRender' => true,
                        'ns' => SummaryForm::DATA_SCOPE_SUMMARY_ADDRESS_FORM,
                        'externalProvider' => SummaryForm::DATA_SCOPE_SUMMARY_ADDRESS_FORM
                            . '.' . SummaryForm::DATA_SCOPE_SUMMARY_ADDRESS_FORM
                            . '_data_source',
                        'formSubmitType' => 'ajax'
                    ],
                ],
            ]
        ];
    }

    /**
     * Returns address info insert form name depends on "isShipping" param
     *
     * @return string
     */
    private function getAddressInfoInsertFormName()
    {
        return $this->isShippingFieldSet()
            ? self::SHIPPING_INFORMATION_INSERT_FORM
            : self::BILLING_INFORMATION_INSERT_FORM;
    }

    /**
     * Checks if it is shipping address form.
     *
     * @return bool
     */
    private function isShippingFieldSet()
    {
        return $this->isShipping;
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