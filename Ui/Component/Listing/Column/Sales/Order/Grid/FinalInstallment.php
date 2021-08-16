<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\Sales\Order\Grid;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class FinalInstallment - modifying final date if it exist
 */
class FinalInstallment extends Column
{
    /**
     * Add final installment to subscriptions profile page.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (!empty($item['subscription_final_installment_date'])) {
                    if ($item['subscription_final_installment_date']
                        == $item['subscription_first_installment_date']
                    ) {
                        $item['subscription_final_installment_date'] = "--";
                    }
                }
            }
        }

        return $dataSource;
    }
}
