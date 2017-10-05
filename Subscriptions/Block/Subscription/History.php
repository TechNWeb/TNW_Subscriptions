<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Block\Subscription;

use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Grid\CollectionFactory;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as SubscriptionProfileManager;

/**
 * Subscriptions history block instance.
 */
class History extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Grid\Collection
     */
    private $subscriptions;

    /**
     * @var CollectionFactory
     */
    private $subscriptionCollectionFactory;

    /**
     * Convert price value helper
     *
     * @var PriceCurrencyInterface
     */
    private $priceFormatter;

    /**
     * Status options source
     *
     * @var ProfileStatus
     */
    private $profileStatus;

    /**
     * @var Manager
     */
    private $profileOrderManager;

    /**
     * @var SubscriptionProfileManager
     */
    private $subscriptionProfileManager;

    /**
     * History constructor.
     *
     * @param Session $customerSession
     * @param PriceCurrencyInterface $priceFormatter
     * @param ProfileStatus $profileStatus
     * @param Manager $profileOrderManager
     * @param Context $context
     * @param SubscriptionProfileManager $subscriptionProfileManager
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Session $customerSession,
        PriceCurrencyInterface $priceFormatter,
        ProfileStatus $profileStatus,
        Manager $profileOrderManager,
        Context $context,
        SubscriptionProfileManager $subscriptionProfileManager,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->priceFormatter = $priceFormatter;
        $this->profileStatus = $profileStatus;
        $this->profileOrderManager = $profileOrderManager;
        $this->subscriptionProfileManager = $subscriptionProfileManager;
        $this->subscriptionCollectionFactory = $collectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve subscription profiles collection.
     *
     * @return bool|\TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Grid\Collection
     */
    public function getSubscriptionsCollection()
    {
        if (!($customerId = $this->customerSession->getCustomerId())) {
            return false;
        }
        if (!$this->subscriptions) {
            $collection = $this->subscriptionCollectionFactory->create();

            $this->subscriptions = $collection->addFieldToFilter(
                'main_table.customer_id',
                ['eq' => $customerId]
            )->setOrder(
                'created_at',
                'desc'
            );
        }

        return $this->subscriptions;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getSubscriptionsCollection()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'sales.order.history.pager'
            )->setCollection(
                $this->getSubscriptionsCollection()
            );
            $this->setChild('pager', $pager);
            $this->getSubscriptionsCollection()->load();
        }
        return $this;
    }

    /**
     * Retrieve format price.
     * e.g. $11.99
     *
     * @param $price
     * @param string $currencyCode
     * @return float
     */
    public function formatPrice($price, $currencyCode)
    {
        $result = null;

        if ($price) {
            $currencyCode = ($currencyCode) ? $currencyCode : null;
            $result = $this->priceFormatter->format(
                $price,
                false,
                null,
                null,
                $currencyCode
            );
        }

        return $result;
    }

    /**
     * Retrieve profile status label.
     *
     * @param string $status
     * @return null|string
     */
    public function getStatusLabel($status)
    {
        return $this->profileStatus->getLabelByValue($status);
    }

    /**
     * Retrieve status class for appearance.
     *
     * @param string $status
     * @return string
     */
    public function getStatusClass($status)
    {
        $result = '';

        switch ($status) {
            case ProfileStatus::STATUS_ACTIVE:
            case ProfileStatus::STATUS_HOLDED:
            case ProfileStatus::STATUS_TRIAL:
            case ProfileStatus::STATUS_PENDING:
            case ProfileStatus::STATUS_COMPLETE:
                $result = 'green';
                break;
            case ProfileStatus::STATUS_SUSPENDED:
                $result = 'red';
                break;
            case ProfileStatus::STATUS_CANCELED:
            case ProfileStatus::STATUS_PAST_DUE:
                $result = 'orange';
                break;
        }

        return $result;
    }

    /**
     * Retrieve icon class for appearance.
     *
     * @param string $status
     * @return string
     */
    public function getIconSubClass($status)
    {
        $result = '';

        switch ($status) {
            case ProfileStatus::STATUS_ACTIVE:
            case ProfileStatus::STATUS_TRIAL:
            case ProfileStatus::STATUS_HOLDED:
                $result = 'sub-icon-active-green';
                if ($this->checkCreditCardExpire()) {
                    $result = 'sub-icon-warning-orange';
                }
                break;
            case ProfileStatus::STATUS_PENDING:
            case ProfileStatus::STATUS_PAST_DUE:
                $result = 'sub-icon-warning-orange';
                break;
            case ProfileStatus::STATUS_SUSPENDED:
                $result = 'sub-icon-warning-red';
                break;
        }

        return $result;
    }

    /**
     * Retrieve status message.
     *
     * @param int|string $status
     * @param $scheduledAt
     * @return string
     */
    public function getStatusMessage($status, $scheduledAt)
    {
        return $this->profileOrderManager->getStatusMessage($status, $scheduledAt);
    }

    /**
     * Check if we can hold subscription.
     *
     * @return bool
     */
    public function canHoldSubscription()
    {
        //todo add check logic to hold subscription
        return true;
    }

    /**
     * Check if we can cancel subscription.
     *
     * @return bool
     */
    public function canCancelSubscription()
    {
        //todo add check logic to cancel subscription
        return true;
    }

    /**
     * Return edit url.
     *
     * @param object $subscription
     * @return string
     */
    public function getEditUrl($subscription)
    {
        return $this->getUrl('tnw_subscriptions/subscription/edit', ['entity_id' => $subscription->getId()]);
    }

    /**
     * Return hold url.
     *
     * @param object $subscription
     * @return string
     */
    public function getHoldUrl($subscription)
    {
        return $this->getUrl(
            'tnw_subscriptions/subscription_actions/UpdateStatus',
            [
                'entity_id' => $subscription->getId(),
                'status' => ProfileStatus::STATUS_HOLDED
            ]
        );
    }

    /**
     * Return cancel url.
     *
     * @param object $subscription
     * @return string
     */
    public function getCancelUrl($subscription)
    {
        return $this->getUrl(
            'tnw_subscriptions/subscription_actions/UpdateStatus',
            [
                'entity_id' => $subscription->getId(),
                'status' => ProfileStatus::STATUS_CANCELED
            ]
        );
    }

    /**
     * Return back url.
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('tnw_subscriptions/subscription/history');
    }

    /**
     * Return pager block html.
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    private function checkCreditCardExpire()
    {
        //todo add logic to check expire date credit card
        return false;
    }
}
