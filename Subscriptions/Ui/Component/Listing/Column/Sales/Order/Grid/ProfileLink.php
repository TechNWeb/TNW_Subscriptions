<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\Sales\Order\Grid;

use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;

/**
 * Order id column modifier.
 */
class ProfileLink extends Column
{
    /**
     * Add link to subscriptions profile page.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {

                if (isset($item[SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID])) {
                    $url = $this->context->getUrl(
                        'tnw_subscriptions/subscriptionprofile/edit/',
                        ['entity_id' => $item[SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID]]
                    );
                    $gridLabel = SubscriptionProfileInterface::LABEL_PREFIX
                        . $item[SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID];
                    $html = sprintf(
                        "<a target=\"_blank\" href ='%s'\">%s</a>",
                        $url,
                        $gridLabel
                    );
                    $item[SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID] = $html;
                }
            }
        }

        return $dataSource;
    }
}
