<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ProductBillingFrequency;

use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as RecurringOptionRepository;
use TNW\Subscriptions\Model\BillingFrequencyRepository;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;

/**
 * Class ReadHandler - product frequencies read handler
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
     * ReadHandler constructor.
     * @param RecurringOptionRepository $recurringOptionRepository
     * @param BillingFrequencyRepository $billingFrequencyRepository
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
     * @return bool|object
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @param $option
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
