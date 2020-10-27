<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form;
use TNW\Subscriptions\Model\ProfileCcUtils;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\ProfitCalculator;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as ProfileOrderManager;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\Framework\Stdlib\DateTime\Timezone;

/**
 * Dashboard for Subscription Profile.
 */
class Dashboard extends BaseFormModifier
{
    /**
     * Group name.
     */
    const GROUP_DASHBOARD = 'dashboard';

    /**
     * Data Persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * Profit calculator
     *
     * @var ProfitCalculator
     */
    private $profitCalculator;

    /**
     * @var ProfileCcUtils
     */
    private $utils;

    /**
     * @var ProfileOrderManager
     */
    private $profileOrderManager;

    /**
     * @var Timezone
     */
    private $timezone;

    /**
     * @param UrlInterface $urlBuilder
     * @param Registry $registry
     * @param DataPersistorInterface $dataPersistor
     * @param ProfitCalculator $profitCalculator
     * @param ProfileOrderManager $profileOrderManager
     * @param ProfileCcUtils $utils
     * @param Timezone $timezone
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Registry $registry,
        DataPersistorInterface $dataPersistor,
        ProfitCalculator $profitCalculator,
        ProfileOrderManager $profileOrderManager,
        ProfileCcUtils $utils,
        Timezone $timezone
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->profitCalculator = $profitCalculator;
        $this->profileOrderManager = $profileOrderManager;
        $this->utils = $utils;
        $this->timezone = $timezone;
        parent::__construct($urlBuilder, $registry);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta[static::GROUP_DASHBOARD] = [
            'children' => [
                static::GROUP_DASHBOARD . '_listing' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => true,
                                'componentType' => Container::NAME,

                            ],
                        ],
                    ],
                ],
                'subscription_details_fieldset' => $this->getSubscriptionDetailsFieldsetMeta(),
                'profit_fieldset' => $this->getProfitFieldsetMeta(),
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Subscription Change History'),
                        'collapsible' => true,
                        'opened' => true,
                        'componentType' => Form\Fieldset::NAME,
                        'sortOrder' => 10,
                        'tabMessages' => $this->getTabMessages(),
                    ],
                ],
            ],
        ];

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $profile = $this->getProfile();

        if ($profile) {
            $this->dataPersistor->set('subscription_id', $profile->getId());
        }

        return $data;
    }

    /**
     * Retrieve attention messages for tab.
     *
     * @return array
     */
    protected function getTabMessages()
    {
        $messages = [];

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

        // Say that profile will be canceled next cycle
        if ($profile
            && $profile->getStatus() != ProfileStatus::STATUS_CANCELED
            && $profile->getCancelBeforeNextCycle()
        ) {
            $date = $this->getCancelBeforeNextCycleDate();
            if ($date) {
                $messages[] = sprintf(__("Subscription will be canceled on %s"), $date ?: '--');
            }
        }

        return $messages;
    }

    /**
     * Retrieve cancel date in case of cancellation is delayed.
     *
     * @return string
     */
    private function getCancelBeforeNextCycleDate()
    {
        $result = false;
        $nextPayment = $this->profileOrderManager->getNextProfileRelation($this->getProfile());
        if ($nextPayment) {
            $result = $this->timezone->formatDate($nextPayment->getScheduledAt(), \IntlDateFormatter::LONG);
        }

        return $result;
    }

    /**
     * Returns warning messages for subscription details area.
     *
     * @return array
     */
    private function getSubscriptionDetailsMessage()
    {
        $messages = [];
        $profile = $this->getProfile();
        if ($profile && $profile->getNeedRecollect()) {
            $messages[] = $profile->getShippingBillingChangesMadeMessageForSubscriptionDetails();
        }
        return $messages;
    }

    /**
     * Returns subscription details fieldset meta information
     *
     * @return array
     */
    private function getSubscriptionDetailsFieldsetMeta()
    {
        return [
            'children' => [
                'subscription_details' => [
                    'children' => [
                        'subscription_details_message' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'messages' => $this->getSubscriptionDetailsMessage()
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns warning messages for profit area.
     *
     * @return array
     */
    private function getProfitMessage()
    {
        $messages = [];
        $profile = $this->getProfile();
        if ($profile && $profile->getProductNeedRecollect()) {
            $messages[] = $profile->getProductChangesMadeMessageForProfit();
        }
        return $messages;
    }

    /**
     * Returns label for profit block
     *
     * @return string
     */
    private function getProfitLabel()
    {
        $profit = $this->profitCalculator->getRenderedTotalProfit($this->getProfile(), false);
        return __('Profit (Total: %1)', $profit);
    }

    /**
     * Returns profit fieldset meta information
     *
     * @return array
     */
    private function getProfitFieldsetMeta()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => $this->getProfitLabel()
                    ],
                ],
            ],
            'children' => [
                'profit' => [
                    'children' => [
                        'profit_message' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'messages' => $this->getProfitMessage()
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
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
}
