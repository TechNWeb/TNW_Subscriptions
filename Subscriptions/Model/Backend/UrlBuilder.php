<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Backend;

use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\UrlBuilderInterface;

/**
 * Class to retrieve subscription url
 */
class UrlBuilder implements UrlBuilderInterface
{
    /**
     * URL path to edit subscription
     * 
     * @var string 
     */
    private $editPath = 'tnw_subscriptions/subscriptionprofile/edit/';

    /**
     * Url Builder
     *
     * @var UrlInterface
     */
    private $baseUrlBuilder;

    /**
     * @param UrlInterface $baseUrlBuilder
     */
    public function __construct(UrlInterface $baseUrlBuilder)
    {
        $this->baseUrlBuilder = $baseUrlBuilder;
    }

    /**
     * Get subscription edit URL
     * 
     * @param $id
     * @return string
     */
    public function getEditUrl($id)
    {
        return $this->baseUrlBuilder->getUrl($this->editPath, ['entity_id' => $id]);
    }

    /**
     * Get subscription edit URL link
     * 
     * @param $id
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