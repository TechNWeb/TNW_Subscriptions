<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Checkout\ConfigProvider;

use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Model\Checkout\ConfigProviderInterface;

/**
 * Products config for Cart.
 */
class Products implements ConfigProviderInterface
{
    /**
     * Url Builder.
     *
     * @var UrlInterface
     */
    private $url;

    /**
     * Products constructor.
     * @param UrlInterface $url
     */
    public function __construct(
        UrlInterface $url
    ) {
        $this->url = $url;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        return [
            'render_url' => $this->url->getUrl('tnw_subscriptions/ui_render/handle'),
        ];
    }
}
