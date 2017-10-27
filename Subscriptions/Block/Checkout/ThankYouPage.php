<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Checkout;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use TNW\Subscriptions\Model\Backend\UrlBuilder;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;

/**
 * Checkout thank you page block.
 */
class ThankYouPage extends Template
{
    /**
     * Subscription url builder.
     *
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * Session.
     *
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * Subscription profile manager.
     *
     * @var Manager
     */
    private $profileManager;

    /**
     * @inheritdoc
     */
    protected $_template = 'TNW_Subscriptions::checkout/thankyoupage.phtml';

    /**
     * ThankYouPage constructor.
     *
     * @param UrlBuilder $urlBuilder
     * @param QuoteSessionInterface $quoteSession
     * @param Manager $profileManager
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        UrlBuilder $urlBuilder,
        QuoteSessionInterface $quoteSession,
        Manager $profileManager,
        Context $context,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->session = $quoteSession;
        $this->profileManager = $profileManager;
        parent::__construct($context, $data);
    }

    /**
     * Return created subscription profiles ids.
     *
     * @return array
     */
    public function getCreatedProfilesIds()
    {
        return $this->session->getProfileIds();
    }

    /**
     * Retrieve created subscription label text.
     *
     * @param int $profileId
     * @return string
     */
    public function getSubscriptionListElementInfo($profileId)
    {
        $profileLinkHtml = $this->getSubscriptionEditUrlHtml($profileId);
        return sprintf(__("Subscription %s created"), $profileLinkHtml);
    }

    /**
     * Retrieve subscription edit url html.
     *
     * @param int $profileId
     * @return string
     */
    private function getSubscriptionEditUrlHtml($profileId)
    {
        return $this->urlBuilder->getEditHtmlLink($profileId,true);
    }
}
