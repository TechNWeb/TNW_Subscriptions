<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Ui\Component\Form;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

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
        if ($profile && $profile->getGenerateQuotesState() == SubscriptionProfileInterface::GENERATE_QUOTES_STATE_NEED_GENERATE) {
            $messages[] = __('We are finalizing the subscription profile. NOTE: Not all upcoming order are displayed, check back later to see more date on this tab.');
        }
        if ($profile && $profile->getProductNeedRecollect()) {
            $messages[] = $profile->getShippingBillingChangesMadeMessageForUpcomingOrders();
        }

        return $messages;
    }
}