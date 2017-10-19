<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Edit\Request\Save\Profile;

use TNW\Subscriptions\Model\SubscriptionProfile\Address\Manager as ProfileAddressManager;

/**
 * Save addresses processor.
 */
class Address extends Base
{
    /**
     * @var string
     */
    private $type;

    /**
     * Subscription profile manager
     *
     * @var ProfileAddressManager
     */
    private $profileAddressManager;

    /**
     * @param ProfileAddressManager $profileAddressManager
     * @param $type
     */
    public function __construct(
        ProfileAddressManager $profileAddressManager,
        $type
    ) {
        $this->profileAddressManager = $profileAddressManager;
        $this->type = $type;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $this->profileAddressManager->processAddress($data, $this->type);
    }
}
