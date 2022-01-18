<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\Report;

use Magento\Sales\Ui\Component\Listing\Column\Price;

/**
 * UiComponent class for Price format column with price total
 */
class PriceWithTotal extends Price
{
    /**
     * {@inheritDoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        $dataSource = parent::prepareDataSource($dataSource);

        if (!empty($dataSource['data']['totals'])) {
            parent::prepareDataSource([
                'data' => [
                    'items' => [& $dataSource['data']['totals']]
                ]
            ]);
        }

        return $dataSource;
    }
}
