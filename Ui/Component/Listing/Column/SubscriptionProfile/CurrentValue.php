<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Collection;

/**
 * Class Current Value column
 */
class CurrentValue extends Column
{
    /**
     * Subscription profiles collection
     *
     * @var Collection
     */
    private $profileCollection;

    /**
     * Convert price value helper
     *
     * @var PriceCurrencyInterface
     */
    private $priceFormatter;
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Collection $profileCollection
     * @param PriceCurrencyInterface $priceFormatter
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Collection $profileCollection,
        PriceCurrencyInterface $priceFormatter,
        array $components = [],
        array $data = []
    ) {
        $this->profileCollection = $profileCollection;
        $this->priceFormatter = $priceFormatter;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $quoteItemsData = $this->profileCollection->getQuoteItemsData($dataSource['data']['items']);
            foreach ($dataSource['data']['items'] as & $item) {
                $profileId = $item['entity_id'];
                if (key_exists($profileId, $quoteItemsData)) {
                    $currentValue = $quoteItemsData[$profileId];
                } else {
                    $currentValueArray = $this->profileCollection->getCurrentValues([$profileId]);
                    $currentValue = count($currentValueArray) ? (float) array_shift($currentValueArray) : 0;
                }
                $currencyCode = isset($item[SubscriptionProfileInterface::PROFILE_CURRENCY_CODE])
                    ? $item[SubscriptionProfileInterface::PROFILE_CURRENCY_CODE]
                    : null;
                $currentValue = $this->priceFormatter->format(
                    $currentValue,
                    false,
                    null,
                    null,
                    $currencyCode
                );
                $item[$this->getData('name')] = $currentValue;
            }
        }

        return $dataSource;
    }
}
