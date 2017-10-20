<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Edit\Request\Save\Profile;

use TNW\Subscriptions\Model\SubscriptionProfile\Process\ProcessInterface;

/**
 * Base save processor.
 */
abstract class Base implements ProcessInterface
{
    /**
     * List of save errors.
     *
     * @var array
     */
    protected $errors;

    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        return is_array($this->errors) ? $this->errors : [];
    }
}
