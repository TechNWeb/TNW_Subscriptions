<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Quote\Model\Quote;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;
use TNW\Subscriptions\Model\Source\ShippingMethods;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Class SummaryShippingMethodForm
 */
class SummaryShippingMethodForm extends AbstractDataProvider
{
    /**
     * Form data scope.
     */
    const FORM_NAME = 'tnw_subscriptionprofile_summary_shipping_method_form';

    const SHIPPING_DETAILS_HEADER = 'shipping_method_header';
    const SHIPPING_DETAILS_FIELDSET = 'shipping_method';
    const SHIPPING_DETAILS_EDIT_FIELD = 'edit_shipping_method';
    const SHIPPING_METHOD_EDIT_FIELDSET = 'shipping_method_edit';
    const SHIPPING_METHOD_ID_FIELD = 'shipping_method_id';

    /**
     * Subscription profile
     *
     * @var SubscriptionProfile
     */
    private $profile;

    /**
     * Profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * Next quote
     *
     * @var Quote
     */
    private $nextQuote;

    /**
     * Shipping methods source
     *
     * @var ShippingMethods
     */
    private $shippingMethods;

    /**
     * SummaryForm constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ProfileManager $profileManager
     * @param ShippingMethods $shippingMethods
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ProfileManager $profileManager,
        ShippingMethods $shippingMethods,
        array $meta = [],
        array $data = []
    ) {
        $this->profileManager = $profileManager;
        $this->profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        $this->nextQuote = $this->profileManager->getNextQuote();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->shippingMethods = $shippingMethods;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data =  [];

        $data[SummaryInsertForm::FORM_DATA_KEY] = $this->getProfileId();
        $data[self::SHIPPING_METHOD_ID_FIELD] =
            $this->profileManager->getProfile()->getShippingMethod();

        return [
            $this->getProfileId() => $data
        ];
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        $meta = array_merge_recursive(
            $meta,
            [
                self::SHIPPING_DETAILS_FIELDSET => [
                    'children' => [
                        self::SHIPPING_DETAILS_HEADER => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'content' => __('Shipping Details'),
                                    ],
                                ],
                            ],
                            'children' => [
                                self::SHIPPING_DETAILS_EDIT_FIELD => [
                                    'arguments' => [
                                        'data' => $this->getEditFieldConfig(),
                                    ],
                                ]
                            ]
                        ],
                        self::SHIPPING_METHOD_EDIT_FIELDSET => [
                            'children' => [
                                self::SHIPPING_METHOD_ID_FIELD => [
                                    'arguments' => [
                                        'data' => [
                                            'options' => $this->getShippingMethodOptions()
                                        ],
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
            ]
        );
        return $meta;
    }

    /**
     * Returns current subscription profile id from registry
     *
     * @return mixed|null|string
     */
    private function getProfileId()
    {
        return $this->profile ? $this->profile->getId() : null;
    }

    /**
     * Returns edit field config
     *
     * @return array
     */
    private function getEditFieldConfig()
    {
        $isEditVisible = false;

        if ($this->profile && $this->profile->canEditProfile()) {
            $isEditVisible = (bool)$this->nextQuote;
        }

        $editFieldConfig = [
            'config' => [
                'visible' => $isEditVisible,
                'imports' => [
                    'visible' => $isEditVisible ? 'ns = ${ $.ns }, index = shipping_method:preview' : ''
                ]
            ],
        ];

        return $editFieldConfig;
    }

    /**
     * Returns shipping method options
     *
     * @return array
     */
    private function getShippingMethodOptions()
    {
        $options = [];
        if ($this->nextQuote) {
            $options = $this->shippingMethods->getShippingMethodOptions($this->nextQuote);
            $shippingMethodsCodesWithoutWarning = $this->shippingMethods->getDontCostDependedMethodsCodes();
            foreach ($options as $key=> $option) {
                $options[$key]['css'] = 'subscription-shipping-attention';
                $options[$key]['title'] = $this->shippingMethods->getShippingAttentionMessage();
                foreach ($shippingMethodsCodesWithoutWarning as $code) {
                    if(strpos($option['value'], $code) === 0) {
                        $options[$key]['css'] = '';
                        $options[$key]['title'] = '';
                        break;
                    }
                }
            }
        }
        return $options;
    }
}
