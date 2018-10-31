<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Queue\Status;

use Magento\Framework\Data\OptionSourceInterface;
use TNW\Subscriptions\Model\Source\Queue\Status;

/**
 * Class Status options
 */
class Options implements OptionSourceInterface
{
    /**
     * Status options source
     *
     * @var Status
     */
    private $queueStatus;

    /**
     * Constructor
     *
     * @param Status $queueStatus
     */
    public function __construct(
        Status $queueStatus
    ) {
        $this->queueStatus = $queueStatus;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->queueStatus->getAllOptions();
    }
}