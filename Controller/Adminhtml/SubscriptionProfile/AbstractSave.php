<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;
use TNW\Subscriptions\Model\Processor\Request as RequestProcessor;

/**
 * Abstract class for profile admin save actions.
 */
abstract class AbstractSave extends SubscriptionProfile
{
    /**
     * Save processor model.
     *
     * @var RequestProcessor
     */
    private $saveProcessor;

    /**
     * AbstractSave constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param RequestProcessor $saveProcessor
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        RequestProcessor $saveProcessor
    ) {
        $this->saveProcessor = $saveProcessor;
        parent::__construct($context, $coreRegistry, $dataPersistor);
    }

    /**
     * Returns save processor model.
     *
     * @return RequestProcessor
     */
    protected function getSaveProcessor()
    {
        return $this->saveProcessor;
    }

    /**
     * Create processing response as JSON object.
     *
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
                            $needReload = $error['needReload'] ? true : false;
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

    /**
     * Add processing errors to message manager to show them in message block on page.
     *
     * @param $errors
     * @return void
     */
    protected function addErrorsToMessageManager($errors)
    {
        foreach ($errors as $error) {
            if (!empty($error)) {
                if (is_array($error)) {
                    $this->messageManager->addErrorMessage(reset($error));
                } else {
                    $this->messageManager->addErrorMessage($error);
                }
            }

        }
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_Subscriptions::SubscriptionProfile_edit'
        );
    }
}
