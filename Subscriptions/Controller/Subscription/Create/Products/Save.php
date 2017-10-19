<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription\Create\Products;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Controller\Subscription\AbstractSave;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Processor;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Saves modified subscription item.
 */
class Save extends AbstractSave
{
    /**
     * Subscription create model.
     *
     * @var CreateProfile
     */
    private $createModel;

    /**
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
        $result = $this->getSaveProcessor()->processSave(
            $this->getRequest()->getParams()
        );
        $response = $this->getJsonResponse($result);
        if (!$result){
            $response['objects_count'] = count(
                $this->createModel->getSession()->getSubQuoteIds()
            );
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)
            ->setData($response);
    }
}
