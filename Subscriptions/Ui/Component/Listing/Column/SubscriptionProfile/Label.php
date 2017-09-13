<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile;

use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Class Label Column
 */
class Label extends Column
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
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[SubscriptionProfileInterface::ID])) {
                    $url = $this->context->getUrl(
                        'tnw_subscriptions/subscriptionprofile/edit/',
                        ['entity_id' => $item[SubscriptionProfileInterface::ID]]
                    );
                    $item[SubscriptionProfileInterface::LABEL] = [
                        'edit' => [
                            'href' => $url,
                            'label' => $item[SubscriptionProfileInterface::LABEL],
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
