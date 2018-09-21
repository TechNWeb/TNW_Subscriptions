<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Backend;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\UrlBuilderInterface;

/**
 * Class to retrieve subscription url
 */
class UrlBuilder implements UrlBuilderInterface
{
    /**
     * URL admin path to edit subscription
     * 
     * @var string 
     */
    private $adminEditPath = 'tnw_subscriptions/subscriptionprofile/edit/';

    /**
     * URL frontend path to edit subscription
     *
     * @var string
     */
    private $frontendEditPath = 'tnw_subscriptions/subscription/edit/';

    /**
     * Url Builder
     *
     * @var UrlInterface
     */
    private $baseUrlBuilder;

    /**
     * App state.
     *
     * @var State
     */
    private $state;

    /**
     * @param UrlInterface $baseUrlBuilder
     * @param State $state
     */
    public function __construct(UrlInterface $baseUrlBuilder, State $state)
    {
        $this->baseUrlBuilder = $baseUrlBuilder;
        $this->state = $state;
    }

    /**
     * Get subscription edit URL
     * 
     * @param int $id
     * @return string
     */
    public function getEditUrl($id)
    {
        $currentPath = $this->frontendEditPath;
        if ($this->state->getAreaCode() === Area::AREA_ADMINHTML) {
            $currentPath = $this->adminEditPath;
        }

        return $this->baseUrlBuilder->getUrl($currentPath, ['entity_id' => $id]);
    }

    /**
     * Get subscription edit URL link
     * 
     * @param int $id
     * @param bool $targetBlank
     * @return string
     */
    public function getEditHtmlLink($id, $targetBlank = false)
    {
        $url = $this->getEditUrl($id);
        $label = SubscriptionProfileInterface::LABEL_PREFIX . $id;
        $html = sprintf(
            "<a%s href=\"%s\">%s</a>",
            $targetBlank ? ' target="_blank"' : '',
            $url,
            $label
        );
        
        return $html;
    }
}
