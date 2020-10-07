<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use Magento\Framework\View\Element\Context;
use Magento\Framework\Message\ManagerInterface;
use TNW\Subscriptions\Model\Backend\UrlBuilder;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager;
use TNW\Subscriptions\Model\SubscriptionProfileOrderRepository;

/**
 * Class ProfileSuccessViewModel - block for profile success on checkout
 */
class ProfileSuccessViewModel implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var SubscriptionProfileOrderRepository
     */
    private $profileOrderRepo;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var Manager
     */
    private $profileOrderManager;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param UrlBuilder $urlBuilder
     * @param SubscriptionProfileOrderRepository $profileOrderRepo
     * @param Manager $profileOrderManager
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        UrlBuilder $urlBuilder,
        SubscriptionProfileOrderRepository $profileOrderRepo,
        Manager $profileOrderManager,
        SubscriptionProfileRepositoryInterface $profileRepository,
        ManagerInterface $messageManager
    ) {
        $this->localeDate = $context->getLocaleDate();
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->urlBuilder = $urlBuilder;
        $this->profileOrderRepo = $profileOrderRepo;
        $this->profileRepository = $profileRepository;
        $this->profileOrderManager = $profileOrderManager;
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

    /**
     * @param $profileId
     * @return string
     */
    public function getNextPaymentDate($profileId)
    {
        try {
            $date = $this->profileOrderManager
                ->getNextProfileRelation($this->profileRepository->getById($profileId))
                ->getScheduledAt();
            return $this->localeDate->formatDate($date, \IntlDateFormatter::LONG);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addExceptionMessage($e);
        }
    }
}
