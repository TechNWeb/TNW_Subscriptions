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
 * Class PaidInstallment
 */
class PaidInstallment extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components,
        array $data
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

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
                        if (count($options) > 1 && $options[1] != 0) {
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
