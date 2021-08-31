<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\Sales\Order\Grid;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ExpireCc - modifying data about cc expiration to Yes or No format
 */
class ExpireCc extends Column
{
    /**
     * Add Expiretion status to subscriptions profile page.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['subscription_expire_cc'])) {
                    if ($item['subscription_expire_cc'] == 0) {
                        $item['subscription_expire_cc'] = __("No") ;
                    } else {
                        $item['subscription_expire_cc'] = __("Yes") ;
                    }
                } else {
                    $item['subscription_expire_cc'] = "--";
                }
            }
            return $dataSource;
        }
    }
}
