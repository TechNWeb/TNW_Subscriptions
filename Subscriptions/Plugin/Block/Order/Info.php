<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Block\Order;

use TNW\Subscriptions\Api\UrlBuilderInterface as UrlBuilderInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager;

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
     * @var Manager
     */
    private $manager;

    /**
     * @param UrlBuilderInterface $profileUrlBuilder
     * @param Manager $manager
     */
    public function __construct(
        UrlBuilderInterface $profileUrlBuilder,
        Manager $manager
    ) {
        $this->profileUrlBuilder = $profileUrlBuilder;
        $this->manager = $manager;
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
            $profile_id = $this->manager->getSubscriptionProfileIdByOrder($orderId);
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