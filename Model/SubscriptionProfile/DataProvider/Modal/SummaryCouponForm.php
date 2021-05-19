<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Quote\Model\Quote;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Class SummaryShippingMethodForm - used for shipping method summary
 */
class SummaryCouponForm extends AbstractDataProvider
{
    /**
     * Form data scope.
     */
    const FORM_NAME = 'tnw_subscriptionprofile_summary_coupon_form';
    const COUPON_DETAILS_FIELDSET = 'coupon';
    const COUPON_DETAILS_HEADER = 'coupon_header';
    const COUPON_DETAILS_EDIT_FIELD = 'edit_coupon';
    const COUPON_EDIT_FIELDSET = 'coupon_edit';
    const COUPON_ID_FIELD = 'coupon_id';

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
     * SummaryForm constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ProfileManager $profileManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ProfileManager $profileManager,
        array $meta = [],
        array $data = []
    ) {
        $this->profileManager = $profileManager;
        $this->profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        $this->nextQuote = $this->profileManager->getNextQuote();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data =  [];

        $data[SummaryInsertForm::FORM_DATA_KEY] = $this->getProfileId();
        $data[SubscriptionProfileInterface::COUPON_CODE] = $this->profile->getCouponCode();

        return [
            $this->getProfileId() => $data
        ];
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
        return $this;
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
                self::COUPON_DETAILS_FIELDSET => [
                    'children' => [
                        self::COUPON_DETAILS_HEADER => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'content' => __('Coupon Details'),
                                    ],
                                ],
                            ],
                            'children' => [
                                self::COUPON_DETAILS_EDIT_FIELD => [
                                    'arguments' => [
                                        'data' => $this->getEditFieldConfig(),
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
            $isEditVisible = (null !== $this->nextQuote);
        }

        $editFieldConfig = [
            'config' => [
                'visible' => $isEditVisible,
                'imports' => [
                    'visible' => $isEditVisible ? 'ns = ${ $.ns }, index = coupon:preview' : '',
                    '__disableTmpl' => [
                        'visible' => false
                    ]
                ]
            ],
        ];

        return $editFieldConfig;
    }
}
