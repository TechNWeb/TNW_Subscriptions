<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin;

use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Backend\Session\Quote;


abstract class AbstractCreate
{
    /**
     * First part of path to subscription fields.
     */
    const SUBSCRIPTION_BUY_REQUEST_PARAM_NAME = 'subscription_data';

    /**
     * @var Context
     */
    private $context;

    /**
     * Session.
     *
     * @var Quote
     */
    private $session;

    /**
     * AbstractCreate constructor.
     * @param Context $context
     * @param Quote $session
     */
    public function __construct(
        Context $context,
        Quote $session
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
     * @return Quote
     */
    public function getSession()
    {
        return $this->session;
    }
}