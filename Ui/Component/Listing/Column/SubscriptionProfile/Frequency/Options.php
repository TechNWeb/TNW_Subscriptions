<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Frequency;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface;

/**
 * Class Options - ui component
 */
class Options implements OptionSourceInterface
{
    /**
     * @var BillingFrequencyRepositoryInterface
     */
    private $frequencyRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Options constructor.
     * @param BillingFrequencyRepositoryInterface $frequencyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        BillingFrequencyRepositoryInterface $frequencyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->frequencyRepository = $frequencyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray()
    {
        $result = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $frequencies = $this->frequencyRepository->getList($searchCriteria)->getItems();
        foreach ($frequencies as $frequency) {
            $result[] = [
                'value' => $frequency->getLabel(),
                'label' => $frequency->getLabel()
            ];
        }
        return $result;
    }
}
