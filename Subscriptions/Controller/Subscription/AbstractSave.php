<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription;

use Magento\Framework\App\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Processor;

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
     * Save processor model.
     *
     * @var Processor
     */
    private $saveProcessor;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Processor $saveProcessor
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Processor $saveProcessor
    ) {
        $this->resultPageFactory = $resultPageFactory;
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
