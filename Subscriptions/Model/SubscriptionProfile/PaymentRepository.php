<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentSearchResultsInterfaceFactory;
use TNW\Subscriptions\Api\PaymentRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment\CollectionFactory;

/**
 * Repository for subscription profile payment
 */
class PaymentRepository implements PaymentRepositoryInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment
     */
    private $resourceModel;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\PaymentFactory
     */
    private $paymentFactory;

    /**
     * @var SubscriptionProfilePaymentSearchResultsInterfaceFactory
     */
    private $paymentSearchResults;

    /**
     * @param CollectionFactory $collectionFactory
     * @param \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment $resourceModel
     * @param PaymentFactory $paymentFactory
     * @param SubscriptionProfilePaymentSearchResultsInterfaceFactory $paymentSearchResults
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment $resourceModel,
        PaymentFactory $paymentFactory,
        SubscriptionProfilePaymentSearchResultsInterfaceFactory $paymentSearchResults
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resourceModel = $resourceModel;
        $this->paymentFactory = $paymentFactory;
        $this->paymentSearchResults = $paymentSearchResults;
    }

    /**
     * Save subscription payment to DB
     *
     * @param SubscriptionProfilePaymentInterface $subscriptionPayment
     * @return SubscriptionProfilePaymentInterface
     * @throws CouldNotSaveException
     */
    public function save(SubscriptionProfilePaymentInterface $subscriptionPayment)
    {
        try {
            if ($subscriptionPayment->getProfileId() && $subscriptionPayment->getEngineCode()) {
                $this->resourceModel->save($subscriptionPayment);
            } else {
                throw new CouldNotSaveException(
                    __('Could not save the subscription payment. Profile ID or engine code is not found')
                );
            }
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__(
                'Could not save the subscription payment: %1',
                $e->getMessage()
            ));
        }
        return $subscriptionPayment;
    }

    /**
     * Get payment by id
     *
     * @param string $paymentId int
     * @return SubscriptionProfilePaymentInterface
     * @throws NoSuchEntityException
     */
    public function getById($paymentId)
    {
        $subscriptionProfile = $this->paymentFactory->create();
        $this->resourceModel->load($subscriptionProfile, $paymentId);

        if (!$subscriptionProfile->getId()) {
            throw new NoSuchEntityException(__('SubscriptionProfile with id "%1" does not exist.',
                $paymentId));
        }

        return $subscriptionProfile;
    }


    /**
     * Get subscription payment list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResults
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var SearchResults $searchData */
        $searchData = $this->paymentSearchResults->create();
        $searchData->setSearchCriteria($searchCriteria);
        $collection = $this->collectionFactory->create();

        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }

        $searchData->setTotalCount($collection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();

        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection() == SortOrder::SORT_ASC ? 'ASC' : 'DESC'
                );
            }
        }

        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $searchData->setItems($collection->getItems());

        return $searchData;
    }

    /**
     * Delete subscription payment
     *
     * @param SubscriptionProfilePaymentInterface $subscriptionPayment
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(SubscriptionProfilePaymentInterface $subscriptionPayment)
    {
        try {
            $this->resourceModel->delete($subscriptionPayment);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the subscription profile payment: %1',
                $exception->getMessage()
            ));
        }

        return true;
    }

    /**
     * Delete subscription payment
     *
     * @param string $id
     * @return bool
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }
}
