<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\ProcessInterface;

/**
 * Base save processor.
 */
abstract class Base implements ProcessInterface
{
    /**
     * Subscription create model.
     *
     * @var CreateProfile
     */
    private $createModel;

    /**
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * List of save errors.
     *
     * @var array
     */
    protected $errors;

    /**
     * Processor constructor.
     * @param CreateProfile $createModel
     */
    public function __construct(
        CreateProfile $createModel,
        QuoteSessionInterface $session
    ) {
        $this->createModel = $createModel;
        $this->session = $session;
    }

    /**
     * Returns value from array by key or default value.
     *
     * @param array $data
     * @param string $field
     * @param int|string|null|bool $default
     * @return mixed
     */
    protected function getFieldValue(array $data, $field, $default)
    {
        return !empty($data[$field]) ? $data[$field] : $default;
    }

    /**
     * Returns subscription create model.
     *
     * @return CreateProfile
     */
    protected function getSubCreateModel()
    {
        return $this->createModel;
    }

    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        return is_array($this->errors) ? $this->errors : [];
    }

    /**
     * Returns session object.
     *
     * @return QuoteSessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }
}