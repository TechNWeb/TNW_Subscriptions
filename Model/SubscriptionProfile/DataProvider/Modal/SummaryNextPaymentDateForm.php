<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use IntlDateFormatter;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Used for next order date summary
 */
class SummaryNextPaymentDateForm extends AbstractDataProvider
{
    /**
     * Form data scope.
     */
    const FORM_NAME = 'tnw_subscriptionprofile_summary_next_payment_date_form';

    const NEXT_PAYMENT_DATE_DETAILS_HEADER = 'next_payment_date_header';
    const NEXT_PAYMENT_DATE_DETAILS_FIELDSET = 'next_payment_date';
    const NEXT_PAYMENT_DATE_DETAILS_EDIT_FIELD = 'edit_next_payment_date';
    const NEXT_PAYMENT_DATE_EDIT_FIELDSET = 'next_payment_date_edit';
    const NEXT_PAYMENT_DATE_VALUE_FIELD = 'next_payment_date_value';
    const NEXT_PAYMENT_DATE_VIEW_FIELD = 'next_payment_date_view';

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
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * SummaryForm constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ProfileManager $profileManager
     * @param TimezoneInterface $localeDate
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ProfileManager $profileManager,
        TimezoneInterface $localeDate,
        array $meta = [],
        array $data = []
    ) {
        $this->profileManager = $profileManager;
        $this->profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY)
            ?? $this->profileManager->loadProfileFromRequest('entity_id');
        $this->nextQuote = $this->profileManager->getNextQuote();
        $this->localeDate = $localeDate;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function getData()
    {
        $data =  [];

        $data[SummaryInsertForm::FORM_DATA_KEY] = $this->getProfileId();
        $data['entity_id'] = $this->getProfileId();
        $scheduledAt = $this->profileManager->getNextProfileRelation()->getScheduledAt();
        $data[self::NEXT_PAYMENT_DATE_VALUE_FIELD] = $scheduledAt;
        $data[self::NEXT_PAYMENT_DATE_VIEW_FIELD]
            = $this->localeDate->formatDate($scheduledAt, IntlDateFormatter::MEDIUM);

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
                self::NEXT_PAYMENT_DATE_DETAILS_FIELDSET => [
                    'children' => [
                        self::NEXT_PAYMENT_DATE_DETAILS_HEADER => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'content' => __('Next Payment Date'),
                                    ],
                                ],
                            ],
                            'children' => [
                                self::NEXT_PAYMENT_DATE_DETAILS_EDIT_FIELD => [
                                    'arguments' => [
                                        'data' => $this->getEditFieldConfig(),
                                    ],
                                ]
                            ]
                        ],
                        self::NEXT_PAYMENT_DATE_EDIT_FIELDSET => [
                            'children' => [
                                self::NEXT_PAYMENT_DATE_VALUE_FIELD => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'options' => $this->getDatePickerOptions(),
                                            ],
                                        ],
                                    ],
                                ]
                            ],
                        ]
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
                    'visible' => $isEditVisible ? 'ns = ${ $.ns }, index = next_payment_date:preview' : '',
                    '__disableTmpl' => [
                        'visible' => false
                    ]
                ]
            ],
        ];

        return $editFieldConfig;
    }

    /**
     * Min and Max date for datepicker
     * @return array
     */
    private function getDatePickerOptions()
    {
        $minDate = $this->localeDate->date()->format('m/d/Y');
        try {
            if ((int)$this->profile->getStatus() === ProfileStatus::STATUS_TRIAL) {
                $maxDate = $this->localeDate->date($this->profile->getStartDate())->format('m/d/Y');
            } else {
                $lastOrderDate = $this->profileManager->getLastSuccessfulProfileRelation()->getScheduledAt();
                $frequency = (int)$this->profile->getFrequency();
                $unit = (int)$this->profileManager->getProfile()->getUnit() === BillingFrequencyUnitType::DAYS
                    ? 'D'
                    : 'M';
                $maxDate = $this->localeDate->date($lastOrderDate)
                    ->add(new \DateInterval('P1D'))
                    ->add(new \DateInterval('P' . $frequency * 2 . $unit))->format('m/d/Y');
            }
        } catch (\Exception $e) {
            $maxDate = null;
        }
        $result = [
            'minDate' => $minDate,
        ];
        if ($maxDate) {
            $result['maxDate'] = $maxDate;
        }

        return $result;
    }
}
