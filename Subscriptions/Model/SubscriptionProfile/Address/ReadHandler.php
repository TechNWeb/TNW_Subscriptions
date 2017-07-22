<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Address;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\AddressRepository;

/**
 * Loads profile addresses and sets to subscription profile.
 */
class ReadHandler implements ExtensionInterface
{
    /**
     * Repository for saving/retrieving profile addresses.
     *
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * ReadHandler constructor.
     * @param AddressRepository $addressRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        AddressRepository $addressRepository,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->addressRepository = $addressRepository;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * @param SubscriptionProfileInterface $entity
     * @param array $arguments
     * @return SubscriptionProfileInterface
     */
    public function execute($entity, $arguments = [])
    {
        $this->criteriaBuilder->addFilter(
            SubscriptionProfileAddressInterface::PROFILE_ID,
            $entity->getId()
        );
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->criteriaBuilder->create();

        $addresses = $this->addressRepository->getList($searchCriteria)->getItems();
        $entity->setAddresses($addresses);

        return $entity;
    }
}
