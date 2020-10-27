<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Sales\Model\Order;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use TNW\Subscriptions\Api\SubscriptionProfileOrderRepositoryInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;

/**
 * Class ItemRepository - plugin to add new extension attribute
 */
class ItemRepository
{
    /**
     * @var OrderItemExtensionFactory
     */
    private $extensionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SubscriptionProfileOrderRepositoryInterface
     */
    private $profileOrderRepository;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * ItemRepository constructor.
     * @param OrderItemExtensionFactory $extensionFactory
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SubscriptionProfileOrderRepositoryInterface $profileOrderRepository
     * @param SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     */
    public function __construct(
        OrderItemExtensionFactory $extensionFactory,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SubscriptionProfileOrderRepositoryInterface $profileOrderRepository,
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
    ) {
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->profileOrderRepository = $profileOrderRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->extensionFactory = $extensionFactory;
    }

    /**
     *
     * @param OrderItemRepositoryInterface $subject
     * @param OrderItemInterface $orderItem
     *
     * @return OrderItemInterface
     */
    public function afterGet(OrderItemRepositoryInterface $subject, OrderItemInterface $orderItem)
    {
        $this->setItemExtensionAttribute($orderItem);
        return $orderItem;
    }

    /**
     *
     * @param OrderItemRepositoryInterface $subject
     * @param OrderItemSearchResultInterface $searchResult
     *
     * @return OrderItemSearchResultInterface
     */
    public function afterGetList(OrderItemRepositoryInterface $subject, OrderItemSearchResultInterface $searchResult)
    {
        $orderItems = $searchResult->getItems();
        foreach ($orderItems as &$item) {
            $this->setItemExtensionAttribute($item);
        }
        return $searchResult;
    }

    /**
     * @param $item
     */
    private function setItemExtensionAttribute($item)
    {
        $options = $item->getProductOptions();
        if (array_key_exists('info_buyRequest', $options)
            && array_key_exists('subscription_data', $options['info_buyRequest'])
        ) {
            $orderId = $item->getOrderId();
            $filter = $this->filterBuilder
                ->setField('magento_order_id')
                ->setConditionType('eq')
                ->setValue($orderId)
                ->create();
            $this->searchCriteriaBuilder->addFilters([$filter]);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            try {
                $profileRelations = $this->profileOrderRepository->getList($searchCriteria)->getItems();
            } catch (\Exception $e) {
                $profileRelations = [];
            }
            if ($profileRelations) {
                foreach ($profileRelations as $relation) {
                    $proposedShippingDate = $this->getProposedShippingDate($relation, $item);
                }
                if (isset($proposedShippingDate) && $proposedShippingDate) {
                    $extensionAttributes = $item->getExtensionAttributes();
                    $extensionAttributes = $extensionAttributes
                        ? $extensionAttributes
                        : $this->extensionFactory->create();
                    $extensionAttributes->setProposedShippingDate($proposedShippingDate);
                    $item->setExtensionAttributes($extensionAttributes);
                }
            }
        }
    }

    /**
     * @param $relation
     * @param $item
     * @return string|null
     */
    private function getProposedShippingDate($relation, $item)
    {
        $proposedShippingDate = '';
        try {
            $profile = $this->subscriptionProfileRepository->getById($relation->getSubscriptionProfileId());
            foreach ($profile->getProducts() as $product) {
                $profileStartDate = $profile->getTrialStartDate() ?: $profile->getStartDate();
                if ($product->getMagentoProductId() == $item->getProductId()
                    && strtotime($profileStartDate) > strtotime($item->getCreatedAt())
                ) {
                    $proposedShippingDate = $profileStartDate;
                }
            }
        } catch (\Exception $e) {
            $proposedShippingDate = '';
        }
        return $proposedShippingDate;
    }
}
