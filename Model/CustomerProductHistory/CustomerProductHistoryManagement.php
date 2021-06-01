<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\CustomerProductHistory;

use Magento\Customer\Model\Session;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use TNW\Subscriptions\Api\CustomerProductHistoryManagementInterface;
use TNW\Subscriptions\Api\Data\CustomerProductHistoryInterface;
use TNW\Subscriptions\Model\CustomerProductHistoryRepository;
use TNW\Subscriptions\Model\ResourceModel\CustomerProductHistory\CollectionFactory;

/**
 * Class CustomerProductHistoryManagement
 */
class CustomerProductHistoryManagement implements CustomerProductHistoryManagementInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var CustomerProductHistoryRepository
     */
    private $customerProductHistoryRepository;

    /**
     * @var CollectionFactory
     */
    private $customerProductHistoryCollectionFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * CustomerProductHistoryManagement constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param CustomerProductHistoryRepository $customerProductHistoryRepository
     * @param CollectionFactory $customerProductHistoryCollectionFactory
     * @param Session $customerSession
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        CustomerProductHistoryRepository $customerProductHistoryRepository,
        CollectionFactory $customerProductHistoryCollectionFactory,
        Session $customerSession
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->customerProductHistoryRepository = $customerProductHistoryRepository;
        $this->customerProductHistoryCollectionFactory = $customerProductHistoryCollectionFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * {@inheritdoc}
     */
    public function isProductTrialAvailableForCustomer(int $customerId, int $productId): bool
    {
        $customerFilter = $this->filterBuilder
            ->setField(CustomerProductHistoryInterface::CUSTOMER_ID)
            ->setConditionType('eq')
            ->setValue($customerId)
            ->create();

        $productFilter = $this->filterBuilder
            ->setField(CustomerProductHistoryInterface::MAGENTO_PRODUCT_ID)
            ->setConditionType('eq')
            ->setValue($productId)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilters([$customerFilter])
            ->addFilters([$productFilter])
            ->create();

        $result = $this->customerProductHistoryRepository->getList($searchCriteria);
        return !$result->getTotalCount();
    }

    /**
     * {@inheritdoc}
     */
    public function getUniqueProductsInSubscriptionsForCustomer(int $customerId): array
    {
        $collection = $this->customerProductHistoryCollectionFactory->create()
            ->addFieldToSelect(CustomerProductHistoryInterface::MAGENTO_PRODUCT_ID)
            ->addFieldToFilter(CustomerProductHistoryInterface::CUSTOMER_ID, $customerId);

        $collection->getSelect()
            ->group(CustomerProductHistoryInterface::MAGENTO_PRODUCT_ID);

        $products = [];
        foreach ($collection->getItems() as $item) {
            $products[] = (int)$item->getData('magento_product_id');
        }
        return $products;
    }

    /**
     * {@inheritdoc}
     */
    public function getUniqueProductsInSubscriptionsForCurrentCustomer(): array
    {
        $customerProductHistoryList = [];
        if ($this->customerSession->isLoggedIn() && $this->customerSession->getCustomerId()) {
            $customerProductHistoryList = $this->getUniqueProductsInSubscriptionsForCustomer(
                (int)$this->customerSession->getCustomerId()
            );
        }
        return $customerProductHistoryList;
    }
}
