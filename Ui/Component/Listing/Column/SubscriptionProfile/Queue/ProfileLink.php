<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Queue;

use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Model\SubscriptionProfile;

class ProfileLink extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {

                if (isset($item[$fieldName])) {
                    $url = $this->context->getUrl('tnw_subscriptions/subscriptionprofile/edit/', ['entity_id' => $item['subscription_profile_id']]);
                    $html = sprintf(
                        "<a target=\"_blank\" href ='%s'\">%s</a>",
                        $url,
                        SubscriptionProfile::LABEL_PREFIX . $item[$fieldName]
                    );
                    $item[$fieldName] = $html;
                }
            }
        }

        return $dataSource;
    }
}
