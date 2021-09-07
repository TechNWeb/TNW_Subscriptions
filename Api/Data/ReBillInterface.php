<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

/**
 * Interface ReBillInterface - used to describe the data model for subscription re-bill
 */
interface ReBillInterface
{
    /**
     * @param $profileIds
     * @return mixed
     */
    public function setSubscriptionProfiles($profileIds);

    /**
     * @return mixed
     */
    public function getSubscriptionProfiles();

    /**
     * @param $queueIds
     * @return mixed
     */
    public function setQueues($queueIds);

    /**
     * @return mixed
     */
    public function getQueues();
}
