<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\Backend\Block\Template;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Block\Subscription\Summary\Overview\Message;
use TNW\Subscriptions\Block\Subscription\Summary\Overview\MissedPayments;
use TNW\Subscriptions\Block\Subscription\Summary\Overview\NextPayment;
use TNW\Subscriptions\Block\Subscription\Summary\Overview\Status;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder\Collection as ProfileOrderCollection;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileOrder;

/**
 * Subscription Overview block
 */
class Overview extends Template
{
    /**
     * @inheritdoc
     */
    protected $_template = 'TNW_Subscriptions::subscription_profile/summary/overview.phtml';

    /**
     * Registry model
     *
     * @var \Magento\Framework\Registry
     */
    private $registry;

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
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ProfileOrderCollection $profileOrderCollection ,
     * @param ProfileManager $profileManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        ProfileOrderCollection $profileOrderCollection,
        ProfileManager $profileManager,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->profileOrderCollection = $profileOrderCollection;
        $this->profileManager = $profileManager;
        parent::__construct($context, $data);
    }

    /**
     * Get current Subscription Profile model
     *
     * @return SubscriptionProfile|null
     */
    public function getSubscriptionProfile()
    {
        return $this->registry->registry('tnw_subscription_profile');
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
     * @param Template $block
     * @return Template
     */
    protected function initChildBlock(Template $block)
    {
        $this->profileManager->setProfile($this->getSubscriptionProfile());
        $block->addData([
            'subscription_profile' => $this->getSubscriptionProfile(),
            'next_profile_relation' => $this->getNextProfileRelation(),
            'next_quote'=> $this->getNextQuote(),
        ]);
        return $block;
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
        return $this->getSubscriptionProfile()->getStatus() != ProfileStatus::STATUS_COMPLETE;
    }

    public function getShippingInfoHtml()
    {
        return 'shipping information';
    }

    public function getShippingDetailsHtml()
    {
        return 'shiiping details';
    }

    public function getBillingInfoHtml()
    {
        return 'billing information';
    }

    public function getPaymentDetailsHtml()
    {
        return 'payment details';
    }

    public function getProductsHtml()
    {
        return 'products grid';
    }

    public function getDangerZoneHtml()
    {
        return 'DangerZone block';
    }
}
