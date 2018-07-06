<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Edit;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Order id column modifier.
 */
class OrderLink extends Column
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
                    $url = $this->context->getUrl('sales/order/view/', ['order_id' => $item['entity_id']]);
                    $html = sprintf(
                        "<a target=\"_blank\" href ='%s'\">%s</a>",
                        $url,
                        $item[$fieldName]
                    );
                    $item[$fieldName] = $html;
                }
            }
        }

        return $dataSource;
    }
}