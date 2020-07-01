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
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\ProfitCalculator;

/**
 * Class Current Value column
 */
class CurrentValue extends Column
{
    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var ProfitCalculator
     */
    private $profitCalculator;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param ProfitCalculator $profitCalculator
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SubscriptionProfileRepositoryInterface $profileRepository,
        ProfitCalculator $profitCalculator,
        array $components = [],
        array $data = []
    ) {
        $this->profileRepository = $profileRepository;
        $this->profitCalculator = $profitCalculator;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $profileId = $item['entity_id'];
                /** @var \TNW\Subscriptions\Model\SubscriptionProfile $profile */
                $profile = $this->profileRepository->getById($profileId);
                $currentValue = $this->profitCalculator->getRenderedAsOfTodayProfit($profile, false);
                $item[$this->getData('name')] = $currentValue;
            }
        }

        return $dataSource;
    }
}
