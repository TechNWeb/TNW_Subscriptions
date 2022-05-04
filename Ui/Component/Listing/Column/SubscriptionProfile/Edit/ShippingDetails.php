<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Edit;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Model\Context;

/**
 * Shipping details column value modifier.
 */
class ShippingDetails extends Column
{
    /**
     * Subscription context.
     *
     * @var Context
     */
    private $subContext;

    /**
     * ShippingDetails constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Context $subContext
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Context $subContext,
        array $components = [],
        array $data = []
    ) {
        $this->subContext = $subContext;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

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
                    $currencyCode = isset($item['order_currency_code'])
                        ? $item['order_currency_code']
                        : null;
                    $shippingPrice = $this->subContext->getPriceCurrency()->format(
                        $item['shipping_and_handling'],
                        false,
                        2,
                        null,
                        $currencyCode
                    );
                    $item[$fieldName] = implode(
                        ' - ',
                        [
                            $item['shipping_information'],
                            $shippingPrice
                        ]
                    );
                } else {
                    $item[$fieldName] = __('N/A');
                }
            }
        }

        return $dataSource;
    }
}
