<?php

namespace TNW\Subscriptions\Ui\Component\Listing\Column\ProductBillingFrequency;

use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = [
                    'edit' => [
                        'label' => __('Edit'),
                        'href' => '#',
                        'callback' => [
                            [
                                'provider' => 'tnw_billingfrequency_product_listing'
                                    . '.tnw_billingfrequency_product_listing.bf_products_columns_editor',
                                'target' => 'startEdit',
                                'params' => [
                                    $item['id'],
                                    false
                                ]
                            ]
                        ]
                    ],
                    'remove' => [
                        'label' => __('Remove'),
                        'href' => '#',
                        'callback' => [
                            [
                                'provider' => 'ns = tnw_billingfrequency_product_listing, index = actions',
                                'target' => 'deleteRecord',
                                'params' => [
                                    $item['id'],
                                    false
                                ]
                            ]
                        ],
                        'confirm' => [
                            'title' => __('Unlink'),
                            'message' => __('Are you sure you want to unlink this product?')
                        ]
                    ]
                ];
            }
        }

        return $dataSource;
    }
}
