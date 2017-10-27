<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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