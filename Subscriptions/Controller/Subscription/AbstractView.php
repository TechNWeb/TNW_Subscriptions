<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

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
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param Registry $registry
     * @param SubscriptionProfileRepository $subscriptionProfileRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        Registry $registry,
        SubscriptionProfileRepository $subscriptionProfileRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->registry = $registry;
        $this->subscriptionRepository = $subscriptionProfileRepository;

        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $subscriptionProfileId = $this->getSubscriptionId();

        if (!$subscriptionProfileId) {
            return $this->noRoutRedirect();
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Subscription (#S-' . $subscriptionProfileId . ')'));

        /** @var \Magento\Framework\View\Element\Html\Links $navigationBlock */
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('tnw_subscriptions/subscription/history');
        }

        return $resultPage;
    }

    /**
     * Get subscription Id and set subscription model (if it exists) to registry.
     *
     * @return bool|int|string
     */
    private function getSubscriptionId()
    {
        $subscriptionId = (int)$this->getRequest()->getParam('entity_id');
        if (!$subscriptionId) {
            return false;
        }

        try {
            /** @var SubscriptionProfile $subscription */
            $subscription = $this->subscriptionRepository->getById($subscriptionId);
        } catch (NoSuchEntityException $e) {
            $subscription = null;

            return false;
        }

        $this->registry->register('tnw_subscription_profile', $subscription);

        return $subscription->getId();
    }

    /**
     * Get redirect for not valid subscriptionId in params.
     *
     * @return Forward
     */
    private function noRoutRedirect()
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();

        return $resultForward->forward('noroute');
    }
}
