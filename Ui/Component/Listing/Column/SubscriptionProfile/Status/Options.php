<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Status;

use Magento\Framework\Data\OptionSourceInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Class Status options
 */
class Options implements OptionSourceInterface
{
    /**
     * Status options source
     *
     * @var ProfileStatus
     */
    private $profileStatus;

    /**
     * Constructor
     *
     * @param ProfileStatus $profileStatus
     */
    public function __construct(
        ProfileStatus $profileStatus
    ) {
        $this->profileStatus = $profileStatus;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->profileStatus->getAllOptions();
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getAllOptions()
    {
        return $this->profileStatus->toOptionArray();
    }
}
