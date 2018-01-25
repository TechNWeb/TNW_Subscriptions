<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Block\Subscription\Info\ContentAbstract;
use TNW\Subscriptions\Block\Subscription\Info\Messages\ExpireWarningSupportInterface;
use TNW\Subscriptions\Block\Subscription\Summary\Overview\Message;
use TNW\Subscriptions\Block\Subscription\Summary\Overview\MissedPayments;
use TNW\Subscriptions\Block\Subscription\Summary\Overview\NextPayment;
use TNW\Subscriptions\Block\Subscription\Summary\Overview\Status;
use TNW\Subscriptions\Model\Config as SubscriptionConfig;
use TNW\Subscriptions\Model\MessagePool;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder\Collection as ProfileOrderCollection;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Model\SubscriptionProfileOrder;

/**
 * Subscription Overview block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Overview extends ContentAbstract implements ExpireWarningSupportInterface
{
    /**
     * Used for redirect path specification.
     */
    const REDIRECT = 'overview_page';

    /**
     * @inheritdoc
     */
    protected $_template = 'TNW_Subscriptions::subscription_profile/summary/overview.phtml';

    /**
     * Next Payment block
     *
     * @var NextPayment
     */
    private $blockNextPayment;

    /**
     * Next Payment block
     *
     * @var Message
     */
    private $blockMessage;

    /**
     * Missed Payments block
     *
     * @var MissedPayments
     */
    private $blockMissedPayments;

    /**
     * Status block
     *
     * @var Status
     */
    private $blockStatus;

    /**
     * @var SubscriptionProfileOrder
     */
    private $nextSubscriptionProfileOrder;

    /**
     * @var ProfileOrderCollection
     */
    private $profileOrderCollection;

    /**
     * Quote
     *
     * @var Quote
     */
    private $nextQuote;

    /**
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * @var SubscriptionConfig
     */
    private $subscriptionConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \TNW\Subscriptions\Model\MessagePool $messagePool
     * @param ProfileOrderCollection $profileOrderCollection ,
     * @param ProfileManager $profileManager
     * @param SubscriptionConfig $subscriptionConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \TNW\Subscriptions\Model\MessagePool $messagePool,
        ProfileOrderCollection $profileOrderCollection,
        ProfileManager $profileManager,
        SubscriptionConfig $subscriptionConfig,
        array $data = []
    ) {
        $this->profileOrderCollection = $profileOrderCollection;
        $this->profileManager = $profileManager;
        $this->subscriptionConfig = $subscriptionConfig;
        parent::__construct($context, $registry, $messagePool, $data);
    }

    /**
     * Retrieve next Subscription profile order
     *
     * @return SubscriptionProfileOrder|false
     */
    public function getNextProfileRelation()
    {
        if ($this->nextSubscriptionProfileOrder === null) {
            $profile = $this->getSubscriptionProfile();
            if (!$profile || !$profile->getId()) {
                $this->nextSubscriptionProfileOrder = false;
            } else {
                $this->nextSubscriptionProfileOrder = $this->profileManager->getNextProfileRelation();
            }
        }

        return $this->nextSubscriptionProfileOrder;
    }

    /**
     * Retrieve next Subscription profile order
     *
     * @return Quote|false
     */
    public function getNextQuote()
    {
        if ($this->nextQuote === null) {
            $profile = $this->getSubscriptionProfile();
            if (!$profile || !$profile->getId()) {
                $this->nextQuote = false;
            } else {
                $this->nextQuote = $this->profileManager->getNextQuote();
            }
        }

        return $this->nextQuote;
    }

    /**
     * Init child block
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function initChildBlock(\Magento\Framework\View\Element\AbstractBlock $block)
    {
        $this->profileManager->setProfile($this->getSubscriptionProfile());
        $block->addData([
            'subscription_profile' => $this->getSubscriptionProfile(),
            'next_profile_relation' => $this->getNextProfileRelation(),
            'next_quote' => $this->getNextQuote(),
        ]);

        return $block;
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        foreach ($this->getChildNames() as $names) {
            $this->initChildBlock($this->getLayout()->getBlock($names));
        }
        $this->prepareTabMessages();

        return parent::_prepareLayout();
    }

    /**
     * Prepare messages for tab.
     *
     * @return void
     */
    private function prepareTabMessages()
    {
        $profile = $this->getSubscriptionProfile();

        // Say that profile will be canceled next cycle
        if ($profile
            && $profile->getStatus() != ProfileStatus::STATUS_CANCELED
            && $profile->getCancelBeforeNextCycle()
        ) {
            $date = $this->getCancelBeforeNextCycleDate();
            if ($date) {
                $this->messagePool->addMessage(
                    \Magento\Framework\Message\MessageInterface::TYPE_WARNING,
                    sprintf(__("Subscription will be canceled on %s"), $date ?: '--')
                );
            }
        }
    }

    /**
     * Retrieve cancel date in case of cancellation is delayed.
     *
     * @return string
     */
    private function getCancelBeforeNextCycleDate()
    {
        $result = false;
        $nextPayment = $this->getNextProfileRelation();
        if ($nextPayment) {
            $result = $this->_localeDate->formatDate($nextPayment->getScheduledAt(), \IntlDateFormatter::LONG);
        }

        return $result;
    }

    /**
     * Retrieve instance of Next Payment block
     *
     * @return NextPayment
     */
    public function getBlockNextPayment()
    {
        if ($this->blockNextPayment === null) {
            $this->blockNextPayment = $this->getLayout()->createBlock(
                NextPayment::class,
                'overview.next-payment'
            );
            $this->initChildBlock($this->blockNextPayment);
        }

        return $this->blockNextPayment;
    }

    /**
     * Return HTML of Next Payment block
     *
     * @return string
     */
    public function getNextPaymentHtml()
    {
        return $this->getBlockNextPayment()->toHtml();
    }

    /**
     * Retrieve instance of Message block
     *
     * @return Message
     */
    public function getBlockMessage()
    {
        if ($this->blockMessage === null) {
            $this->blockMessage = $this->getLayout()->createBlock(
                Message::class,
                'overview.message'
            );
            $this->initChildBlock($this->blockMessage);
        }

        return $this->blockMessage;
    }

    /**
     * Return HTML of Message block
     *
     * @return string
     */
    public function getMessageHtml()
    {
        return $this->getBlockMessage()->toHtml();
    }

    /**
     * Retrieve instance of Missed Payments block
     *
     * @return MissedPayments
     */
    public function getBlockMissedPayments()
    {
        if ($this->blockMissedPayments === null) {
            $this->blockMissedPayments = $this->getLayout()->createBlock(
                MissedPayments::class,
                'overview.missed-payments'
            );
            $this->initChildBlock($this->blockMissedPayments);
        }

        return $this->blockMissedPayments;
    }

    /**
     * Return HTML of Missed Payments block
     *
     * @return string
     */
    public function getMissedPaymentsHtml()
    {
        return $this->getBlockMissedPayments()->toHtml();
    }

    /**
     * Retrieve instance of Status block
     *
     * @return Status
     */
    public function getBlockStatus()
    {
        if ($this->blockStatus === null) {
            $this->blockStatus = $this->getLayout()->createBlock(
                Status::class,
                'overview.status'
            );
            $this->initChildBlock($this->blockStatus);
        }

        return $this->blockStatus;
    }

    /**
     * Return HTML of Status block
     *
     * @return string
     */
    public function getStatusHtml()
    {
        return $this->getBlockStatus()->toHtml();
    }

    /**
     * Can show Next payment block
     *
     * @return bool
     */
    public function getCanShowNextPayment()
    {
        return !in_array(
            $this->getSubscriptionProfile()->getStatus(),
            [
                ProfileStatus::STATUS_COMPLETE,
                ProfileStatus::STATUS_CANCELED,
            ]
        );
    }

    /**
     * Returns shipping info block html.
     *
     * @return string
     */
    public function getShippingInfoHtml()
    {
        return $this->getChildHtml('shipping-information');
    }

    /**
     * @return string
     */
    public function getShippingDetailsHtml()
    {
        return $this->getChildHtml('shipping-details');
    }

    /**
     * Check if it necessary to show shipping details block.
     *
     * @return bool
     */
    public function canShowShippingDetailsBlock()
    {
        return !(bool)$this->getSubscriptionProfile()->getIsVirtual();
    }

    /**
     * Returns billing info block html.
     *
     * @return string
     */
    public function getBillingInfoHtml()
    {
        return $this->getChildHtml('billing-information');
    }

    /**
     * Returns payment details block html.
     *
     * @return string
     */
    public function getPaymentDetailsHtml()
    {
        return $this->getChildHtml('payment-details');
    }

    /**
     * Returns products block html.
     *
     * @return string
     */
    public function getProductsHtml()
    {
        return $this->getChildHtml('products');
    }

    /**
     * Show danger zone block, if at least one option :"Can Place On Hold" or "Can Cancel" are "Yes"
     * and current subscription profile hasn't status "Canceled".
     *
     * @return bool
     */
    public function canShowDangerZone()
    {
        $websiteId = $this->_storeManager->getWebsite()->getId();

        return ($this->subscriptionConfig->getCanHoldProfile($websiteId)
            || $this->subscriptionConfig->getCanCancelProfile($websiteId))
            && (int) $this->getSubscriptionProfile()->getStatus() !== ProfileStatus::STATUS_CANCELED;
    }

    /**
     * Returns danger zone block html.
     *
     * @return string
     */
    public function getDangerZoneHtml()
    {
        return $this->getChildHtml('danger-zone');
    }

    /**
     * Returns subscription messages pool.
     *
     * @return MessagePool
     */
    public function getMessagePool()
    {
        return $this->messagePool;
    }

    /**
     * @return bool
     */
    public function isSupported()
    {
        return $this->_appState->getAreaCode() === \Magento\Framework\App\Area::AREA_FRONTEND;
    }

    /**
     * Return additional class on status message subscription block.
     * Depends on whether the next payment block is displayed.
     *
     * @return string
     */
    public function getStatusMessageBlockClass()
    {
        return !$this->getCanShowNextPayment() ? 'full-block': '';
    }
}
