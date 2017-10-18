<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription;

use Magento\Framework\App\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;
use TNW\Subscriptions\Model\Request\Save\Processor;

/**
 * Abstract controller for subscription creation.
 */
abstract class AbstractSave extends Action\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Subscription create model.
     *
     * @var CreateProfile
     */
    private $createModel;

    /**
     * Save processor model.
     *
     * @var Processor
     */
    private $saveProcessor;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CreateProfile $createModel
     * @param Processor $saveProcessor
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CreateProfile $createModel,
        Processor $saveProcessor
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->createModel = $createModel;
        $this->saveProcessor = $saveProcessor;

        parent::__construct($context);
    }

    /**
     * Returns request same model.
     *
     * @return Processor
     */
    protected function getSaveProcessor()
    {
        return $this->saveProcessor;
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
     * @param array $errors
     * @return array
     */
    protected function getJsonResponse(array $errors)
    {
        $result = ['data' => [], 'error' => false];
        if (!empty($errors)) {
            $result = [
                'error_messages' => $errors,
                'error' => true
            ];
        }

        return $result;
    }
}
