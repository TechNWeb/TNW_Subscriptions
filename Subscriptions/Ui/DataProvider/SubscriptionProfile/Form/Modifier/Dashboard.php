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
     * Dashboard constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param Registry $registry
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Registry $registry,
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
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
        if ($profile && $profile->getNeedGenerateQuotes() == 1) {
            $messages[] = __('We are finalizing the subscription profile. Note, some information from the dashboard may not give the final representation of the customer profile.');
        }

        return $messages;
    }
}
