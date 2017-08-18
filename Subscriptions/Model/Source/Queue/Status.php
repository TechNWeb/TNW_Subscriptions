<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Source\Queue;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Status
 */
class Status implements ArrayInterface
{
    /**
     * List of queue statuses.
     *
     * @var array
     */
    private $options;

    /**#@+
     * Constants for queue status.
     */
    const QUEUE_STATUS_PENDING = 'pending';
    const QUEUE_STATUS_RUNNING = 'running';
    const QUEUE_STATUS_ERROR = 'error';
    const QUEUE_STATUS_COMPLETE = 'complete';
    /**#@-*/

    /**
     * Gets magento object type options array.
     *
     * @return array|null
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [
                self::QUEUE_STATUS_PENDING => __('Pending'),
                self::QUEUE_STATUS_RUNNING => __('Running'),
                self::QUEUE_STATUS_ERROR => __('Error'),
                self::QUEUE_STATUS_COMPLETE => __('Complete'),
            ];
        }

        return $this->options;
    }

    /**
     * Get all options as array of: ['value' => <value>, 'label' => <label>].
     *
     * @return array
     */
    public function getAllOptions()
    {
        $result = [];

        foreach ($this->toOptionArray() as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $result;
    }
}
