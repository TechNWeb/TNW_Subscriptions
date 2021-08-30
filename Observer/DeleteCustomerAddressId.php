<?php

namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\ObserverInterface;
use TNW\Subscriptions\Api\SubscriptionProfileAddressRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

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
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customerEntityId = $observer->getData('customer_address')->getEntityId();

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('customer_address_id', $customerEntityId)->create();
        $profileAddressList = $this->subscriptionProfileAddressRepository->getList($searchCriteria)->getItems();
        if ($profileAddressList) {
            foreach ($profileAddressList as $profileAddress) {
                try {
                    $profileAddress->setCustomerAddressId(null);
                    $this->subscriptionProfileAddressRepository->save($profileAddress);
                } catch (\Exception $exception) {
                    $this->logger->warning($exception->getMessage());
                }
            }
        }
    }
}
