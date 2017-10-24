<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Block\Subscription;

use Magento\Customer\Model\Session;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\ProfileCcUtils;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Grid\CollectionFactory;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as SubscriptionProfileManager;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager;

/**
 * Subscriptions history block instance.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class History extends \Magento\Framework\View\Element\Template
{
    /**
     * Used for redirect path specification.
     */
    const REDIRECT = 'history_page';

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
     * Subscriptions config.
     *
     * @var Config
     */
    private $config;

    /**
     * @var ProfileCcUtils
     */
    private $utils;
    
    /**
     * @var int
     */
    private $websiteId;

    /**
     * @param Session                    $customerSession
     * @param PriceCurrencyInterface     $priceFormatter
     * @param ProfileStatus              $profileStatus
     * @param Manager                    $profileOrderManager
     * @param Context                    $context
     * @param SubscriptionProfileManager $subscriptionProfileManager
     * @param CollectionFactory          $collectionFactory
     * @param Config                     $config
     * @param ProfileCcUtils             $utils
     * @param array                      $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Session $customerSession,
        PriceCurrencyInterface $priceFormatter,
        ProfileStatus $profileStatus,
        Manager $profileOrderManager,
        Context $context,
        SubscriptionProfileManager $subscriptionProfileManager,
        CollectionFactory $collectionFactory,
        Config $config,
        ProfileCcUtils $utils,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->priceFormatter = $priceFormatter;
        $this->profileStatus = $profileStatus;
        $this->profileOrderManager = $profileOrderManager;
        $this->subscriptionProfileManager = $subscriptionProfileManager;
        $this->subscriptionCollectionFactory = $collectionFactory;
        $this->config = $config;
        $this->utils = $utils;
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
            $this->subscriptions = $collection
                ->addFieldToSelect(
                    [
                        'engine_code',
                        'payment_additional_info',
                    ]
                )->addFieldToFilter(
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
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getSubscriptionsCollection()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'sales.order.history.pager'
            )->setCollection(
                $this->getSubscriptionsCollection()
            );
            $this->setChild('pager', $pager);
        }

        return $this;
    }

    /**
     * Retrieve format price.
     * e.g. $11.99
     *
     * @param float $price
     * @param \Magento\Framework\Model\AbstractModel|string|null $currencyCode
     * @return float
     */
    public function formatPrice($price, $currencyCode)
    {
        $result = null;

        if ($price) {
            $currencyCode = $currencyCode ?: null;
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
     * @param \Magento\Framework\DataObject $subscription
     * @return string
     */
    public function getIconSubClass($subscription)
    {
        $result = '';

        switch ($subscription->getStatus()) {
            case ProfileStatus::STATUS_ACTIVE:
            case ProfileStatus::STATUS_TRIAL:
            case ProfileStatus::STATUS_HOLDED:
                $result = 'sub-icon-active-green';
                if ($this->checkCreditCardExpire($subscription)) {
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
     * @param \Magento\Framework\DataObject $subscription
     * @return string
     */
    public function getStatusMessage(\Magento\Framework\DataObject $subscription)
    {
        $activeStatuses = [
            ProfileStatus::STATUS_ACTIVE,
            ProfileStatus::STATUS_HOLDED,
            ProfileStatus::STATUS_TRIAL,
        ];

        if (
            in_array($subscription->getStatus(), $activeStatuses) &&
            $this->checkCreditCardExpire($subscription)
        ) {
            return __('Credit Card will expire before next billing cycle.');
        }

        return $this->profileOrderManager->getStatusMessage(
            $subscription->getStatus(),
            $subscription->getNextBillingCycleDate()
        );
    }

    /** Check if Action Column can be shown. Depends on actions than can be shown.
     *
     * @return bool
     */
    public function canShowActionColumn()
    {
        return $this->canHoldSubscription() || $this->canCancelSubscription();
    }

    /**
     * Check if we can hold subscription.
     *
     * @return bool
     */
    public function canHoldSubscription()
    {
        return $this->config->getCanHoldProfile($this->getWebsiteId());
    }

    /**
     * Check if we can cancel subscription.
     *
     * @return bool
     */
    public function canCancelSubscription()
    {
        return $this->config->getCanCancelProfile($this->getWebsiteId());
    }

    /**
     * Return current website id.
     *
     * @return int
     */
    private function getWebsiteId()
    {
        if (!$this->websiteId) {
            $this->websiteId = $this->_storeManager->getWebsite()->getId();
        }

        return $this->websiteId;
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
     * @param string $subscriptionId
     * @return string
     */
    public function getHoldUrl($subscriptionId)
    {
        return $this->getUrl(
            'tnw_subscriptions/subscription_actions/UpdateStatus',
            [
                'entity_id' => $subscriptionId,
                'status' => ProfileStatus::STATUS_HOLDED,
                'redirect' => self::REDIRECT
            ]
        );
    }

    /**
     * Return cancel url.
     *
     * @param string $subscriptionId
     * @return string
     */
    public function getCancelUrl($subscriptionId)
    {
        return $this->getUrl(
            'tnw_subscriptions/subscription_actions/UpdateStatus',
            [
                'entity_id' => $subscriptionId,
                'status' => ProfileStatus::STATUS_CANCELED,
                'redirect' => self::REDIRECT
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

    /**
     * Check Cc expiration date.
     *
     * @param \Magento\Framework\DataObject $subscription
     * @return bool
     */
    private function checkCreditCardExpire(\Magento\Framework\DataObject $subscription)
    {
        return $this->utils->isCcExpireBy(
            $subscription,
            $subscription->getNextBillingCycleDate()
        );
    }
}
