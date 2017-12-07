<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription;

use Magento\Framework\App\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Model\Processor\Request as RequestProcessor;

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
     * @var RequestProcessor
     */
    private $saveProcessor;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RequestProcessor $saveProcessor
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        RequestProcessor $saveProcessor
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->saveProcessor = $saveProcessor;

        parent::__construct($context);
    }

    /**
     * Returns request same model.
     *
     * @return RequestProcessor
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
            $errorMessages = [];
            $needReload = false;
            foreach ($errors as $error) {
                if (!empty($error)) {
                    if (is_array($error)) {
                        $errorMessages[] = reset($error);
                        if (isset($error['needReload'])) {
                            $needReload = $needReload || ($error['needReload'] ? true : false);
                        }
                    } else {
                        $errorMessages[] = $error;
                    }
                }
            }
            $result = [
                'error_messages' => $errorMessages,
                'error' => true,
                'needReload' => $needReload,
            ];
        }

        return $result;
    }
}
