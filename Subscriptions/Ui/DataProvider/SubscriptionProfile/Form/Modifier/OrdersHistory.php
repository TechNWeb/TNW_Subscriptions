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

/**
 * Prepare orders history ui layout.
 */
class OrdersHistory implements ModifierInterface
{
    const GROUP_ORDER_HISTORY = 'order_history';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param UrlInterface $urlBuilder
     * @param Registry $registry
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Registry $registry
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta[static::GROUP_ORDER_HISTORY] = [
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
                        'label' => false,
                        'collapsible' => false,
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