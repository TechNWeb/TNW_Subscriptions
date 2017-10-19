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
use TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Processor;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Process subscription profile data during creation.
 */
class Process extends AbstractSave
{
    /**
     * Subscription create model.
     *
     * @var CreateProfile
     */
    private $createModel;

    /**
     * Process constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Processor $saveProcessor
     * @param CreateProfile $createModel
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Processor $saveProcessor,
        CreateProfile $createModel
    ) {
        $this->createModel = $createModel;
        parent::__construct($context, $resultPageFactory, $saveProcessor);
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
        $this->createModel->recollectSubscriptions();

        return $result;
    }
}
