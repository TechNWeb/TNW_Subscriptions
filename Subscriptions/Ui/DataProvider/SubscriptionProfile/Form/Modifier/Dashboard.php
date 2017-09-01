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
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Dashboard for Subscription Profile.
 */
class Dashboard implements ModifierInterface
{
    /**
     * Group name.
     */
    const GROUP_DASHBOARD = 'dashboard';

    /**
     * Url Interface.
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Data Persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param UrlInterface $url
     * @param DataPersistorInterface $dataPersistor
     * @param Registry $registry
     */
    public function __construct(
        UrlInterface $url,
        DataPersistorInterface $dataPersistor,
        Registry $registry
    ) {
        $this->urlBuilder = $url;
        $this->dataPersistor = $dataPersistor;
        $this->registry = $registry;
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
        $profile = $this->registry->registry('tnw_subscription_profile');

        if ($profile) {
            $this->dataPersistor->set('subscription_id', $profile->getId());
        }

        return $data;
    }
}
