<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductBillingFrequency;

use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as RecurringOptionRepository;
use TNW\Subscriptions\Model\BillingFrequencyRepository;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;
use TNW\Subscriptions\Model\ProductBillingFrequency;

/**
 * Class ReadHandler
 */
class ReadHandler implements ExtensionInterface
{
    /**
     * @var RecurringOptionRepository
     */
    private $recurringOptionRepository;

    /**
     * @var BillingFrequencyRepository
     */
    private $billingFrequencyRepository;

    /**
     * @param RecurringOptionRepository $recurringOptionRepository
     */
    public function __construct(
        RecurringOptionRepository $recurringOptionRepository,
        BillingFrequencyRepository $billingFrequencyRepository
    ) {
        $this->recurringOptionRepository = $recurringOptionRepository;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
    }

    /**
     * @param object $entity
     * @param array $arguments
     * @return \Magento\Catalog\Api\Data\ProductInterface|object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        $options = [];

        /** @var ProductBillingFrequencyInterface $option */
        foreach ($this->recurringOptionRepository->getListByProductId($entity->getId())->getItems() as $option) {
            $title = 'Billed & Shipped';
            $billingFrequencyData = $this->getBillingFrequencyData($option);
            if ($billingFrequencyData) {
                $title .= ' every %s';
            }
            $title = sprintf(__($title), $billingFrequencyData);
            $option->setTitle($title);
            $option->setProduct($entity);
            $options[] = $option;
        }

        $entity->setRecurringOptions($options);

        return $entity;
    }

    /**
     * Get Billing Frequency data for title in Recurring options grid.
     *
     * @param ProductBillingFrequency $option
     * @return string
     */
    private function getBillingFrequencyData($option)
    {
        $billingFrequencyData = '';
        $billingFrequencyId = $option->getBillingFrequencyId();

        if ($billingFrequencyId) {
            /** @var BillingFrequencyInterface $billingFrequency */
            $billingFrequency = $this->billingFrequencyRepository->getById($billingFrequencyId);

            if ($billingFrequency->getId()) {
                $billingFrequencyData = $this->billingFrequencyRepository
                    ->getBillingFrequencyPeriodLabel($billingFrequency);
            }
        }

        return $billingFrequencyData;
    }
}
