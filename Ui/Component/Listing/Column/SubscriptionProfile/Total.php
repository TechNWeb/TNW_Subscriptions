<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;

/**
 * Class Total Price column
 */
class Total extends Column
{
    /**
     * Convert price value helper
     *
     * @var PriceCurrencyInterface
     */
    private $priceFormatter;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param PriceCurrencyInterface $priceFormatter
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        PriceCurrencyInterface $priceFormatter,
        SubscriptionProfileRepositoryInterface $profileRepository,
        array $components = [],
        array $data = []
    ) {
        $this->priceFormatter = $priceFormatter;
        $this->profileRepository = $profileRepository;
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
            foreach ($dataSource['data']['items'] as & $item) {
                $total = $item['grand_total'] ?? null;
                if ($total) {
                    $currencyCode = $item[SubscriptionProfileInterface::PROFILE_CURRENCY_CODE] ?? null;
                    $currency = $this->priceFormatter->getCurrency(
                        $item['store_id'],
                        $currencyCode
                    );
                    $total = $currency->format($total, false, null);
                }
                $item[$this->getData('name')] = $total;
            }
        }
        return $dataSource;
    }
}
