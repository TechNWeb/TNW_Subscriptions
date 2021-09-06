<?php
/**
 * Copyright © 2020 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\ObserverInterface;
use TNW\Subscriptions\Api\SubscriptionProfileAddressRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;

/**
 * Delete customer_address_id from tnw_subscriptions_subscription_profile_address for correct process profile
 *
 * Class DeleteCustomerAddressId
 */
class DeleteCustomerAddressId implements ObserverInterface
{
    /**
     * @var SubscriptionProfileAddressRepositoryInterface
     */
    private $subscriptionProfileAddressRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * DeleteCustomerAddressId constructor.
     * @param SubscriptionProfileAddressRepositoryInterface $subscriptionProfileAddressRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        SubscriptionProfileAddressRepositoryInterface $subscriptionProfileAddressRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->subscriptionProfileAddressRepository = $subscriptionProfileAddressRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Remove customer_address_id from Subscription Profile Address
     *
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $customerEntityId = $observer->getData('customer_address')->getEntityId();

        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'customer_address_id',
            $customerEntityId
        )->create();
        $profileAddressList = $this->subscriptionProfileAddressRepository
            ->getList($searchCriteria)
            ->getItems();
        if ($profileAddressList) {
            foreach ($profileAddressList as $profileAddress) {
                $profileAddress->setCustomerAddressId(null);
                try {
                    $this->subscriptionProfileAddressRepository->save($profileAddress);
                } catch (\Exception $exception) {
                    $this->logger->warning($exception->getMessage());
                }
            }
        }
    }
}
