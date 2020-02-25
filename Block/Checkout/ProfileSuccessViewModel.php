<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Checkout;

class ProfileSuccessViewModel implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \TNW\Subscriptions\Model\Backend\UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfileOrderRepository
     */
    private $profileOrderRepo;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \TNW\Subscriptions\Model\Backend\UrlBuilder $urlBuilder
     * @param \TNW\Subscriptions\Model\SubscriptionProfileOrderRepository $profileOrderRepo
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \TNW\Subscriptions\Model\Backend\UrlBuilder $urlBuilder,
        \TNW\Subscriptions\Model\SubscriptionProfileOrderRepository $profileOrderRepo
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->urlBuilder = $urlBuilder;
        $this->profileOrderRepo = $profileOrderRepo;
    }

    /**
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface $profileOrder
     *
     * @return string
     */
    public function getEditUrl($profileOrder)
    {
        return $this->urlBuilder->getEditUrl($profileOrder->getSubscriptionProfileId());
    }

    /**
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface $profileOrder
     *
     * @return string
     */
    public function getEditLabel($profileOrder)
    {
        return $this->urlBuilder->getEditLabel($profileOrder->getSubscriptionProfileId());
    }

    /**
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface[] array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProfileOrders()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $filter = $this->filterBuilder
            ->setField('magento_order_id')
            ->setConditionType('eq')
            ->setValue($order->getEntityId())
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder->addFilters([$filter])->create();
        return $this->profileOrderRepo->getList($searchCriteria)->getItems();
    }
}
