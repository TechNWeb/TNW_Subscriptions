<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Ui\Component\Form;

/**
 * Prepare upcoming orders ui layout.
 */
class UpcomingOrders extends BaseFormModifier
{
    /**#@+
     * Upcoming Orders group name.
     */
    const GROUP_UPCOMING_ORDERS = 'upcoming_orders';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta[static::GROUP_UPCOMING_ORDERS] = [
            'children' => [
                'upcoming_orders_listing' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => true,
                                'componentType' => 'insertListing',
                                'dataScope' => 'upcoming_orders_listing',
                                'externalProvider' => 'tnw_subscriptionprofile_edit_upcoming_orders_listing.tnw_subscriptionprofile_edit_upcoming_orders_listing_data_source',
                                'selectionsProvider' => 'tnw_subscriptionprofile_edit_upcoming_orders_listing.tnw_subscriptionprofile_edit_upcoming_orders_listing.tnw_subscriptionprofile_upcoming_orders_columns.ids',
                                'ns' => 'tnw_subscriptionprofile_edit_upcoming_orders_listing',
                                'render_url' => $this->getUrlBuilder()->getUrl('mui/index/render'),
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
        $profile =  $this->getProfile();

         if ($profile && $profile->getId()){
             $data[$profile->getId()]['subscription_profile_id'] = $profile->getId();
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
        if ($profile && $profile->getNeedRecollect()) {
            $messages[] = $profile->getShippingBillingChangesMadeMessageForUpcomingOrders();
        }

        return $messages;
    }
}