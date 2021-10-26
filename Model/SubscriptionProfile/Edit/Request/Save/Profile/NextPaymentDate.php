<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Edit\Request\Save\Profile;

use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Save shipping method processor.
 */
class NextPaymentDate extends Base
{
    /**
     * Subscription profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * @param ProfileManager $profileManager
     */
    public function __construct(
        ProfileManager $profileManager
    ) {
        $this->profileManager = $profileManager;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $process = $this->profileManager->processNextPaymentDate($data);
        if ($process === false) {
            $this->errors[] = __('Only future dates are allowed for Next Payment Date');
        }
    }
}
