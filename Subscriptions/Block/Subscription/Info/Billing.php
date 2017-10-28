<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Info;

use TNW\Subscriptions\Block\Subscription\Info\Messages\ExpireWarningSupportInterface;
use TNW\Subscriptions\Model\MessagePool;

/**
 * Subscription billing block on customer account dashboard.
 */
class Billing extends ContentAbstract implements ExpireWarningSupportInterface
{
    /**
     * @var string
     */
    protected $_template = 'subscription/billing.phtml';

    /**
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
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function initChildBlock(\Magento\Framework\View\Element\AbstractBlock $block)
    {
        $block->addData([
            'subscription_profile' => $this->getSubscriptionProfile(),
        ]);

        return $block;
    }

    /**
     * Return payment methods view form
     *
     * @return string
     */
    public function getPaymentDetailsViewHtml()
    {
        return $this->getChildHtml('payment-details');
    }

    /**
     * Return payment methods edit form.
     *
     * @return string
     */
    public function getPaymentDetailsEditHtml()
    {
        return $this->getChildHtml('tnw_subscriptionprofile_account_payment_method_form');
    }

    /**
     * Check if it necessary to show edit form.
     *
     * @return int
     */
    public function isShowEdit()
    {
        return (int)$this->_request->getParam('payment_details');
    }

}
