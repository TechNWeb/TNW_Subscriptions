<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription\Customer\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Block\Subscription\Info\Shipment;
use TNW\Subscriptions\Controller\Subscription\AbstractSave;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Processor;
use TNW\Subscriptions\Model\SubscriptionProfile\Edit\Response\Processor as ResponseProcessor;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Saves modified subscription profile.
 */
class Save extends AbstractSave
{
    /**
     * Subscription profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var ResponseProcessor
     */
    private $responseProcessor;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Processor $saveProcessor,
        ProfileManager $profileManager,
        Registry $coreRegistry,
        ResponseProcessor $responseProcessor
    ) {
        $this->profileManager = $profileManager;
        $this->coreRegistry = $coreRegistry;
        $this->responseProcessor = $responseProcessor;
        parent::__construct($context, $resultPageFactory, $saveProcessor);
    }


    public function execute()
    {
        $errors = [];
        $request = $this->getRequest()->getParams();
        $result = $this->initProfile();
        if ($result) {
            $errors = $this->processRequestData($request);
        } else {
            $errors[] = __('Subscription profile wasn\'t loaded');
        }

        $response = $this->getJsonResponse($errors);

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)
            ->setData($response);
    }

    /**
     * Processing save post data. Returns list of errors.
     *
     * @return array
     */
    private function processRequestData(array $request)
    {
        return $this->getSaveProcessor()->processSave($request);
    }

    private function initProfile()
    {
        $result = false;
        /** @var SubscriptionProfile $model */
        $model = $this->profileManager
            ->loadProfileFromRequest(Shipment::REQUEST_PROFILE_ID);
        if ($model) {
            $result = true;
            $this->coreRegistry->register('tnw_subscription_profile', $model, true);
        }

        return $result;
    }

    protected function getJsonResponse(array $errors)
    {
        $request = $this->getRequest()->getParams();
        $response = parent::getJsonResponse($errors);
        $response['data'] = $this->responseProcessor->processResponse($request);

        return $response;
    }
}
