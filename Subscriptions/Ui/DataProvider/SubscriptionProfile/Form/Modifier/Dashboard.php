<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form;
use TNW\Subscriptions\Model\SubscriptionProfile\ProfitCalculator;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

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
     * Dashboard constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param Registry $registry
     * @param DataPersistorInterface $dataPersistor
     * @param ProfitCalculator $profitCalculator
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Registry $registry,
        DataPersistorInterface $dataPersistor,
        ProfitCalculator $profitCalculator
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->profitCalculator = $profitCalculator;
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
        if ($profile && $profile->getGenerateQuotesState() == SubscriptionProfileInterface::GENERATE_QUOTES_STATE_NEED_GENERATE) {
            $messages[] = __('We are finalizing the subscription profile. Note, some information from the dashboard may not give the final representation of the customer profile.');
        }

        return $messages;
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
}
