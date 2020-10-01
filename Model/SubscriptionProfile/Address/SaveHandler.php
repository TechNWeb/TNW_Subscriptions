<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Address;

use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\AddressRepository;

/**
 * Saves profile addresses after saving subscription profile.
 */
class SaveHandler implements ExtensionInterface
{
    /**
     * Repository for saving/retrieving profile addresses.
     *
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * SaveHandler constructor.
     * @param AddressRepository $addressRepository
     */
    public function __construct(
        AddressRepository $addressRepository
    ) {
        $this->addressRepository = $addressRepository;
    }

    /**
     * @param object $entity
     * @param array $arguments
     * @return bool|object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($entity, $arguments = [])
    {
        $addresses = $entity->getAddresses();

        if (!empty($addresses)) {
            foreach ($addresses as $address) {
                $address->setProfileId($entity->getId());
                if ($address->getStreet() && is_array($address->getStreet())) {
                    $address->setStreet(trim(implode("\n", $address->getStreet())));
                }
                $this->addressRepository->save($address);
            }
        }

        return $entity;
    }
}
