<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Class SummaryPaymentMethodForm
 */
class SummaryPaymentMethodForm extends AbstractDataProvider
{
    /**
     * Form data scope.
     */
    const FORM_NAME = 'tnw_subscriptionprofile_summary_payment_method_form';

    const PAYMENT_DETAILS_HEADER = 'payment_method_header';
    const PAYMENT_DETAILS_FIELDSET = 'payment_method';

    /**
     * Subscription profile
     *
     * @var SubscriptionProfile
     */
    private $profile;

    /**
     * Subscription profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * Pool of modifiers
     *
     * @var PoolInterface
     */
    private $modifiersPool;

    /**
     * SummaryForm constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ProfileManager $profileManager
     * @param PoolInterface $modifiersPool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ProfileManager $profileManager,
        PoolInterface $modifiersPool,
        array $meta = [],
        array $data = []
    ) {
        $this->profileManager = $profileManager;
        $this->profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        $this->modifiersPool = $modifiersPool;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data =  [];

        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        $data[SummaryInsertForm::FORM_DATA_KEY] = $this->getProfileId();

        $data['payment'][$this->profileManager->getProfile()->getEngineCode()]['method'] = "1";

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
        $poolMeta = [];
        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            if (method_exists($modifier, 'setPaymentFormName')) {
                $modifier->setPaymentFormName(self::FORM_NAME);
            }
            if (method_exists($modifier, 'setAdditionalNamespace')) {
                $modifier->setAdditionalNamespace(self::PAYMENT_DETAILS_FIELDSET);
            }
            if (method_exists($modifier, 'setListens')) {
                $modifier->setListens([]);
            }
            if (method_exists($modifier, 'setProfileId')) {
                $modifier->setProfileId($this->getProfileId());
            }
            $poolMeta = $modifier->modifyMeta($poolMeta);
        }
        $meta = array_merge_recursive(
            $meta,
            [
                self::PAYMENT_DETAILS_FIELDSET => [
                    'children' => array_merge_recursive(
                        $poolMeta,
                        [
                            self::PAYMENT_DETAILS_HEADER => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'content' => __('Payment Details'),
                                        ],
                                    ],
                                ]
                            ]
                        ]
                    ),
                ],
            ]
        );
        return $meta;
    }

    /**
     * Returns current subscription profile id from registry
     *
     * @return null|string
     */
    private function getProfileId()
    {
        return $this->profile ? $this->profile->getId() : null;
    }
}
