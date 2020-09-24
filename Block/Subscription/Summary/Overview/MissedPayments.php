<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary\Overview;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use TNW\Subscriptions\Model\ResourceModel\Queue\Collection as ProfileQueueCollection;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileOrder;

/**
 * Subscription Overview Missed Payments block
 *
 * @method SubscriptionProfile getSubscriptionProfile()
 * @method SubscriptionProfileOrder getNextSubscriptionProfileOrder()
 */
class MissedPayments extends Template
{
    /**
     * @inheritdoc
     */
    protected $_template = 'TNW_Subscriptions::subscription_profile/dashboard/overview/missed-payments.phtml';

    /**
     * @var ProfileQueueCollection
     */
    private $profileQueueCollection;

    /**
     * Count of missed payments
     *
     * @var int
     */
    private $countOfMissedPayments;

    /**
     * @param Context $context
     * @param ProfileQueueCollection $profileQueueCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProfileQueueCollection $profileQueueCollection,
        array $data = []
    ) {
        $this->profileQueueCollection = $profileQueueCollection;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve count of missed payments
     *
     * @return int
     */
    public function getCountOfMissedPayments()
    {
        if ($this->countOfMissedPayments === null) {
            $nextOrder = $this->getNextProfileRelation();
            if (!$nextOrder || !$nextOrder->getId()) {
                $this->countOfMissedPayments = 0;
            } else {
                $select = $this->profileQueueCollection->getSelect()
                    ->reset(\Zend_Db_Select::COLUMNS)
                    ->columns(['attempt_count'])
                    ->where('profile_order_id = ?', $nextOrder->getId());
                $this->countOfMissedPayments = intval($this->profileQueueCollection->getConnection()->fetchOne($select));
            }
        }

        return $this->countOfMissedPayments;
    }
}
