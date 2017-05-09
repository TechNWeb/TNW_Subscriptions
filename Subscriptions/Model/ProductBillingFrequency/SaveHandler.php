<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {

        /** @var ProductBillingFrequencyInterface $option */
        foreach ($this->recurringOptionRepository->getListByProductId($entity->getId())->getItems() as $option){
            $this->recurringOptionRepository->delete($option);
        }

        if ($entity->getRecurringOptions()) {
            foreach ($entity->getRecurringOptions() as $option) {
                $this->recurringOptionRepository->save($option);
            }
        }

        return $entity;
    }
}
