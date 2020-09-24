<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ProductBillingFrequency;

use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as RecurringOptionRepository;

/**
 * Class SaveHandler
 */
class SaveHandler implements ExtensionInterface
{
    /**
     * @var RecurringOptionRepository
     */
    private $recurringOptionRepository;

    /**
     * @param RecurringOptionRepository $recurringOptionRepository
     */
    public function __construct(
        RecurringOptionRepository $recurringOptionRepository
    ) {
        $this->recurringOptionRepository = $recurringOptionRepository;
    }

    /**
     * @param object $entity
     * @param array $arguments
     * @return \Magento\Catalog\Api\Data\ProductInterface|object
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        $entityOptionIds = [];
        if ($entity->getRecurringOptions()) {
            /** @var ProductBillingFrequencyInterface $entityOption */
            foreach ($entity->getRecurringOptions() as $entityOption) {
                $entityOptionIds[] = $this->recurringOptionRepository->save($entityOption)->getId();
            }
        }
        /** @var ProductBillingFrequencyInterface $option */
        foreach ($this->recurringOptionRepository->getListByProductId($entity->getId())->getItems() as $option) {
            if (!in_array($option->getId(), $entityOptionIds)) {
                $this->recurringOptionRepository->delete($option);
            }
        }
        return $entity;
    }
}
