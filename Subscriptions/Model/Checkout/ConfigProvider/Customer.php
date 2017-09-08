<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Checkout\ConfigProvider;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Model\Checkout\ConfigProviderInterface;

/**
 * Customer config for Cart.
 */
class Customer implements ConfigProviderInterface
{
    /**
     * Customer url.
     *
     * @var CustomerUrl
     */
    private $customerUrl;

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
     * @param CustomerUrl $customerUrl
     * @param HttpContext $httpContext
     * @param UrlInterface $url
     */
    public function __construct(
        CustomerUrl $customerUrl,
        HttpContext $httpContext,
        UrlInterface $url
    ) {
        $this->customerUrl = $customerUrl;
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
            'registerUrl' => $this->customerUrl->getRegisterUrl(),
            'forgotPasswordUrl' => $this->customerUrl->getForgotPasswordUrl(),
            'changeAfterLoginUrl' => $this->url->getUrl('tnw_subscriptions/session/ChangeBeforeAuthUrl'),
            'loginPostUrl' => $this->customerUrl->getLoginPostUrl(),
            'urlAddEmailToSession' => $this->url->getUrl('tnw_subscriptions/session/AddEmailToSession'),
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
