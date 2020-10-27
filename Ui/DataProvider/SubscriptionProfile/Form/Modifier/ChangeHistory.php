<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory\CollectionFactory;

/**
 * Data provider for change history.
 */
class ChangeHistory extends BaseFormModifier
{
    /**
     * Group name.
     */
    const GROUP_CHANGE_HISTORY = 'change_history';
    const CHANGE_HISTORY_LISTING = 'tnw_subscriptionprofile_edit_change_history_listing';

    /**
     * Collection Factory.
     *
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * ChangeHistory constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param Registry $registry
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Registry $registry,
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($urlBuilder, $registry);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta[static::GROUP_CHANGE_HISTORY] = [
            'children' => [
                'change_history_listing' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => true,
                                'componentType' => Container::NAME,
                                'component' => 'TNW_Subscriptions/js/grid/history-listing',
                                'dataScope' => 'change_history_listing',
                                'externalProvider' => self::CHANGE_HISTORY_LISTING . '.' . self::CHANGE_HISTORY_LISTING . '_data_source',
                                'selectionsProvider' => self::CHANGE_HISTORY_LISTING . '.'.self::CHANGE_HISTORY_LISTING . 'tnw_subscriptionprofile_change_history_columns.entity_id',
                                'ns' => self::CHANGE_HISTORY_LISTING,
                                'render_url' => $this->getUrlBuilder()->getUrl('mui/index/render'),
                                'realTimeLink' => false,
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'profileId' => '${ $.provider }:data.subscription_profile_id',
                                    '__disableTmpl' => [
                                        'profileId' => false
                                    ]
                                ],
                                'exports' => [
                                    'profileId' => '${ $.externalProvider }:params.subscription_profile_id',
                                    '__disableTmpl' => [
                                        'profileId' => false
                                    ]
                                ],
                                'listens' => [
                                    'tnw_subscriptionprofile_form.areas.change_history:active' => 'forceRender',
                                ]
                            ],
                        ],
                    ],
                ],
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Product Reviews'),
                        'collapsible' => true,
                        'opened' => false,
                        'componentType' => Form\Fieldset::NAME,
                        'sortOrder' => 10,
                        'additionalClasses' => 'order_change_history'
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

        if ($profile && $profile->getId()) {
            $data[$profile->getId()]['subscription_profile_id'] = $profile->getId();
        }

        return $data;
    }
}
