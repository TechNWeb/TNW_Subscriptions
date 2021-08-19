<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\Sales\Order\Grid;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class PaidInstallment - showing how many paid subscriptions
 * orders have been made and how many are left
 */
class PaidInstallment extends Column
{
    /**
     * Add paid installment to subscriptions profile page.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (!empty($item['subscription_paid_installment'])) {
                    $options = explode(",", $item['subscription_paid_installment']);
                    if (isset($options)) {
                        if (count($options) > 1
                            && $options[1] != 0
                            && $options[1] != -1
                            && $options[1] != 1
                        ) {
                            $item['subscription_paid_installment'] = $options[0] . ' / ' . $options[1];
                        } else {
                            $item['subscription_paid_installment'] = $options[0] . ' / ∞';
                        }
                    }
                }
            }
        }

        return $dataSource;
    }
}
