<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;
use Magento\Sales\Model\OrderFactory;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Model\SubscriptionProfileOrderRepository;

/**
 * Abstract controller for subscription information pages.
 */
abstract class AbstractView extends AbstractAccount
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ForwardFactory
     */
    private $resultForwardFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var SubscriptionProfileRepository
     */
    private $subscriptionRepository;

    /**
     * @var OrderViewAuthorizationInterface
     */
    private $orderAuthorization;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var SubscriptionProfileOrderRepository
     */
    private $subscriptionProfileOrder;

    /**
     * @var \Magento\Framework\Controller\Result\Redirect
     */
    private $redirect;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * AbstractView constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param Registry $registry
     * @param SubscriptionProfileRepository $subscriptionProfileRepository
     * @param OrderViewAuthorizationInterface $orderAuthorization
     * @param OrderFactory $orderFactory
     * @param SubscriptionProfileOrderRepository $subscriptionProfileOrder
     * @param RedirectFactory $redirectFactory
     * @param UrlInterface $url
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        Registry $registry,
        SubscriptionProfileRepository $subscriptionProfileRepository,
        OrderViewAuthorizationInterface $orderAuthorization,
        OrderFactory $orderFactory,
        SubscriptionProfileOrderRepository $subscriptionProfileOrder,
        RedirectFactory $redirectFactory,
        UrlInterface $url,
        SearchCriteriaBuilder $criteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->registry = $registry;
        $this->subscriptionRepository = $subscriptionProfileRepository;
        $this->orderAuthorization = $orderAuthorization;
        $this->orderFactory = $orderFactory;
        $this->subscriptionProfileOrder = $subscriptionProfileOrder;
        $this->redirect = $redirectFactory->create();
        $this->url = $url;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->filterBuilder = $filterBuilder;

        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $subscriptionProfileId = $this->getRequest()->getParam('entity_id');

        try {
            if (!$this->canViewSubscriptionById($subscriptionProfileId)) {
                return $this->redirect->setUrl($this->url->getUrl('*/*/history'));
            }
            /** @var SubscriptionProfile $subscription */
            $subscription = $this->subscriptionRepository->getById($subscriptionProfileId);
            $this->registry->register('tnw_subscription_profile', $subscription);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addExceptionMessage($e);
            return $this->redirect->setUrl($this->url->getUrl('*/*/history'));
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set($this->titleSubscription($subscription));

        /** @var \Magento\Framework\View\Element\Html\Links $navigationBlock */
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('tnw_subscriptions/subscription/history');
        }

        return $resultPage;
    }

    /**
     * @param SubscriptionProfile $subscription
     * @return string
     */
    public function titleSubscription($subscription)
    {
        $products = $subscription->getVisibleProducts();
        if (count($products) === 1) {
            return __('%1 Subscription (#S-%2)', reset($products)->getName(), $subscription->getId());
        }

        return __('Subscription (#S-%1) - %2 products', $subscription->getId(), count($products));
    }

    /**
     * Verifying user access to the profile
     *
     * @param $subscriptionProfileId
     * @return bool
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function canViewSubscriptionById($subscriptionProfileId)
    {
        $subscriptionData = $this->subscriptionProfileOrder->getList(
            $this->getFilterByProfileId($subscriptionProfileId)
        )->getItems();
        $subscriptionProfile = end($subscriptionData);
        if ($subscriptionProfile === null) {
            throw new NoSuchEntityException();
        }
        $magentoOrder = $this->orderFactory->create()->load($subscriptionProfile->getMagentoOrderId());
        return $this->orderAuthorization->canView($magentoOrder);
    }

    /**
     * Get criteria filter for Subscription Profile Order Repository
     *
     * @param $subscriptionProfileId
     * @return \Magento\Framework\Api\SearchCriteria
     */
    private function getFilterByProfileId($subscriptionProfileId)
    {
        $this->criteriaBuilder->addFilters([
            $this->filterBuilder
                ->setField('magento_order_id')
                ->setValue(null)
                ->setConditionType('neq')
                ->create()
        ]);
        return $this->criteriaBuilder
            ->addFilter('subscription_profile_id', $subscriptionProfileId)
            ->create();
    }
}
