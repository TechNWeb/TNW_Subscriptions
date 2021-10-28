<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Backend;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Api\UrlBuilderInterface;
use TNW\Subscriptions\Model\Config;

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
     * @var Config
     */
    private $config;

    /**
     * @param UrlInterface $baseUrlBuilder
     * @param State $state
     * @param Config $config
     */
    public function __construct(
        UrlInterface $baseUrlBuilder,
        State $state,
        Config $config
    ) {
        $this->baseUrlBuilder = $baseUrlBuilder;
        $this->state = $state;
        $this->config = $config;
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
     * @param $id
     * @param $websiteId
     * @return string
     */
    public function getEditLabel($id, $websiteId)
    {
        return $this->config->getPrefix($websiteId) . $id;
    }

    /**
     * Get subscription edit URL link
     *
     * @param int $id
     * @param $websiteId
     * @param bool $targetBlank
     * @return string
     */
    public function getEditHtmlLink($id, $websiteId, $targetBlank = false)
    {
        return sprintf(
            "<a%s href=\"%s\">%s</a>",
            $targetBlank ? ' target="_blank"' : '',
            $this->getEditUrl($id),
            $this->getEditLabel($id, $websiteId)
        );
    }
}
