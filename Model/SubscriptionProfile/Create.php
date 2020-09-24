<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Base subscription create class.
 */
class Create
{
    /**
     * First part of path to subscription fields.
     */
    const SUBSCRIPTION_BUY_REQUEST_PARAM_NAME = 'subscription_data';

    /**
     * Last part of path to unique subscription fields in product buy request.
     *
     * Using for checking the ability to add product to subscription quote.
     */
    const UNIQUE = 'unique';

    /**
     * Last part of path to non_unique fields in product buy request.
     */
    const NON_UNIQUE = 'non_unique';

    /**
     * Param to check if we need to create full subscription request.
     *
     * Using on quote generation. In this case we don't need full request to create quote for profile.
     * true - if we creating subscription profile (its our first quote).
     * false - if its quote creation/updating for already existing profile (cron task).
     */
    const FULL_REQUEST_PARAM_NAME = 'full_request';

    /**
     * @var Context
     */
    private $context;

    /**
     * Session.
     *
     * @var \TNW\Subscriptions\Model\QuoteSession
     */
    private $session;

    /**
     * AbstractCreate constructor.
     * @param Context $context
     * @param QuoteSessionInterface $session
     */
    public function __construct(
        Context $context,
        QuoteSessionInterface $session
    ) {
        $this->context = $context;
        $this->session = $session;
    }

    /**
     * Returns Context object.
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Returns subscription admin session.
     *
     * @return \TNW\Subscriptions\Model\QuoteSession
     */
    public function getSession()
    {
        return $this->session;
    }
}
