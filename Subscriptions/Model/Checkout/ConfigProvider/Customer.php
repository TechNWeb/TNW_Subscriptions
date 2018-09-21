<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Checkout\ConfigProvider;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Model\Checkout\ConfigProviderInterface;

/**
 * Customer config for Cart.
 */
class Customer implements ConfigProviderInterface
{
    /**
     * Http context.
     *
     * @var HttpContext
     */
    private $httpContext;

    /**
     * Url.
     *
     * @var UrlInterface
     */
    private $url;

    /**
     * @param HttpContext $httpContext
     * @param UrlInterface $url
     */
    public function __construct(
        HttpContext $httpContext,
        UrlInterface $url
    ) {
        $this->httpContext = $httpContext;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'isCustomerLoggedIn' => $this->isCustomerLoggedIn(),
            'changeAfterLoginUrl' => $this->url->getUrl('tnw_subscriptions/session/ChangeBeforeAuthUrl'),
        ];
    }

    /**
     * Check if customer logged in.
     *
     * @return bool
     */
    private function isCustomerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }
}
