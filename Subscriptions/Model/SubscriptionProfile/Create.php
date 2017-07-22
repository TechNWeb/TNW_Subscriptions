<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Model\Context;
use Magento\Framework\Session\SessionManagerInterface;


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
    const UNIQUE = '/unique';

    /**
     * Last part of path to non_unique fields in product buy request.
     */
    const NON_UNIQUE = '/non_unique';

    /**
     * @var Context
     */
    private $context;

    /**
     * Session.
     *
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * AbstractCreate constructor.
     * @param Context $context
     * @param SessionManagerInterface $session
     */
    public function __construct(
        Context $context,
        SessionManagerInterface $session
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
     * @return SessionManagerInterface
     */
    public function getSession()
    {
        return $this->session;
    }
}