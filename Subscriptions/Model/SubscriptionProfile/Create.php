<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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
     * @var Context
     */
    private $context;

    /**
     * Session.
     *
     * @var QuoteSessionInterface
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
     * @return QuoteSessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }
}
