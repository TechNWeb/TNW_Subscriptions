<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Block\Order;

use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Api\UrlBuilderInterface as UrlBuilderInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder as SubscriptionProfileOrderResource;

/**
 * Plugin for \Magento\Sales\Block\Adminhtml\Order\View\Info
 */
class Info
{
    /**
     * @var UrlBuilderInterface
     */
    private $profileUrlBuilder;

    /**
     * @var SubscriptionProfileOrderResource
     */
    private $subscriptionProfileOrderResource;

    /**
     * @param UrlBuilderInterface $profileUrlBuilder
     */
    public function __construct(
        UrlBuilderInterface $profileUrlBuilder,
        SubscriptionProfileOrderResource $subscriptionProfileOrderResource
    ) {
        $this->profileUrlBuilder = $profileUrlBuilder;
        $this->subscriptionProfileOrderResource = $subscriptionProfileOrderResource;
    }

    /**
     * Add profile link to order Account information
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\View\Info $block
     * @param array $result
     * @return array
     */
    public function afterGetCustomerAccountData(
        \Magento\Sales\Block\Adminhtml\Order\View\Info $block,
        array $result
    ) {
        $orderId = $block->getOrder()->getId();
        if ($orderId) {
            $profile_id = $this->subscriptionProfileOrderResource->getSubscriptionProfileIdByOrder($orderId);
            if ($profile_id) {
                $result[] = [
                    'label' => __('Subscription Profile'),
                    'value' => $this->profileUrlBuilder->getEditHtmlLink($profile_id, true),
                ];
            }
        }

        return $result;
    }
}