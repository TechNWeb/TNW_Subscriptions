<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Info\Messages;

use TNW\Subscriptions\Block\Subscription\Info\ContentAbstract;
use TNW\Subscriptions\Model\ProfileCcUtils;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager;

/**
 * Process Cc expiration warning.
 */
class ExpireWarningProcessor
{
    /**
     * @var ProfileCcUtils
     */
    private $profileCcUtils;

    /**
     * @var Manager
     */
    private $profileOrderManager;

    /**
     * @param Manager        $profileOrderManager
     * @param ProfileCcUtils $profileCcUtils
     */
    public function __construct(
        Manager $profileOrderManager,
        ProfileCcUtils $profileCcUtils
    ) {
        $this->profileOrderManager = $profileOrderManager;
        $this->profileCcUtils = $profileCcUtils;
    }

    /**
     * Process Cc expire message.
     *
     * @param ContentAbstract $block
     * @return void
     */
    public function process(ContentAbstract $block)
    {
        if ($block instanceof ExpireWarningSupportInterface && $block->isSupported()) {
            $relation = $this->getNextProfileRelation($block->getSubscriptionProfile());
            if (false !== $relation &&
                $this->profileCcUtils->isCcExpireBy($block->getSubscriptionProfile(), $relation->getScheduledAt(), true)
            ) {
                $block->getMessagePool()->addMessage(
                    \Magento\Framework\Message\MessageInterface::TYPE_WARNING,
                    __(
                        'Credit Card will expire soon.' .
                        ' <a href="%1">Click here</a> to update your billing information.',
                        $this->getBillingTabUrl($block)
                    )
                );
            }
        }
    }

    /**
     * Returns next profile relation instance.
     *
     * @param SubscriptionProfile $profile
     * @return false|\TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface
     */
    private function getNextProfileRelation(SubscriptionProfile $profile)
    {
        return $this->profileOrderManager->getNextProfileRelation($profile, false);
    }

    /**
     * Returns billing information tab url.
     *
     * @param ContentAbstract $block
     * @return string
     */
    private function getBillingTabUrl(ContentAbstract $block)
    {
        return $block->getUrl(
            'tnw_subscriptions/subscription/billing',
            ['entity_id' => $block->getSubscriptionProfile()->getId()]
        );
    }
}
