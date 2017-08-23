<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\Component\Form;
use Magento\Framework\UrlInterface;
use Magento\Framework\Registry;
use TNW\Subscriptions\Api\SubscriptionProfileMessageHistoryRepositoryInterface;

class ChangeHistory implements ModifierInterface
{
    const GROUP_CHANGE_HISTORY = 'change_history';

    /**
     * @var SubscriptionProfileMessageHistoryRepositoryInterface
     */
    private $messageHistoryRepository;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Registry $registry
     * @param SubscriptionProfileMessageHistoryRepositoryInterface $messageHistoryRepository
     */
    public function __construct(
        Registry $registry,
        SubscriptionProfileMessageHistoryRepositoryInterface $messageHistoryRepository    ) {
        $this->registry = $registry;
        $this->messageHistoryRepository = $messageHistoryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $profile =  $this->registry->registry('tnw_subscription_profile');
        //$this->messageHistoryRepository->getList()

        $meta[static::GROUP_CHANGE_HISTORY] = [
            'children' => [
                'order_history_listing' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => true,
                                'componentType' => 'insertListing',
                                'dataScope' => 'order_history_listing',
                                'externalProvider' => 'tnw_subscriptionprofile_edit_order_history_listing.tnw_subscriptionprofile_edit_order_history_listing_data_source',
                                'selectionsProvider' => 'tnw_subscriptionprofile_edit_order_history_listing.tnw_subscriptionprofile_edit_order_history_listing.tnw_subscriptionprofile_order_history_columns.ids',
                                'ns' => 'tnw_subscriptionprofile_edit_order_history_listing',
                                'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => false,
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'profileId' => '${ $.provider }:data.subscription_profile_id'
                                ],
                                'exports' => [
                                    'profileId' => '${ $.externalProvider }:params.subscription_profile_id'
                                ],
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
                        'sortOrder' => 10
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
        $profile =  $this->registry->registry('tnw_subscription_profile');

         if ($profile && $profile->getId()){
             $data[$profile->getId()]['subscription_profile_id'] = $profile->getId();
         }

        return $data;
    }

}
