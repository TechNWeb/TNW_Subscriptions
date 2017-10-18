<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription\Create;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Controller\Subscription\AbstractSave;
use TNW\Subscriptions\Model\Request\Save\Processor;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Process subscription profile data during creation.
 */
class Process extends AbstractSave
{
    /**
     * Process constructor.
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
        parent::__construct($context, $resultPageFactory, $createModel, $saveProcessor);
    }


    /**
     * Save action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $this->resultFactory;
        $errors = $this->processRequestData();

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)
            ->setData($this->getJsonResponse($errors));
    }

    /**
     * Processing save post data. Returns list of errors.
     *
     * @return array
     */
    private function processRequestData()
    {
        $result = $this->getSaveProcessor()->processSave(
            $this->getRequest()->getParams()
        );
        $this->getSubCreateModel()->recollectSubscriptions();

        return $result;
    }
}
