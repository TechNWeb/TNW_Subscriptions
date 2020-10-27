<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Edit;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\AbstractSave;
use TNW\Subscriptions\Model\Processor\Request as RequestProcessor;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class Save
 */
class Save extends AbstractSave
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * Json factory
     *
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * Subscription profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * Save constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JsonFactory $jsonFactory
     * @param ProfileManager $profileManager
     * @param RequestProcessor $saveProcessor
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JsonFactory $jsonFactory,
        ProfileManager $profileManager,
        RequestProcessor $saveProcessor,
        DataPersistorInterface $dataPersistor
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->jsonFactory = $jsonFactory;
        $this->profileManager = $profileManager;

        parent::__construct($context, $coreRegistry, $dataPersistor, $saveProcessor);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->initProfile();
        if ($result) {
            try {
                /** @var SubscriptionProfile $profile */
                $profile = $this->profileManager->getProfile();
                $profileDataChanges = $profile->hasDataChanges();
                $profile->setDataChanges(false);
                $this->processRequestData();
                if ($profile->hasDataChanges()) {
                    $profile->setNeedRecollect('1');
                }
                $profile->setDataChanges($profileDataChanges || $profile->hasDataChanges());
                $this->profileManager->saveProfile();
            } catch (\Exception $e) {
                $result = false;
            }
        }
        $response = $this->createResponse($result);
        return $this->jsonFactory->create()->setJsonData($response->toJson());
    }

    /**
     * Init subscription profile from Request
     *
     * @return bool
     */
    private function initProfile()
    {
        $result = false;
        /** @var SubscriptionProfile $model */
        $model = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        if ($model) {
            $result = true;
            $this->coreRegistry->register('tnw_subscription_profile', $model, true);
        }
        return $result;
    }

    /**
     * Process request data from request
     */
    private function processRequestData()
    {
        $requestData = $this->getRequest()->getParams();
        $requestData['admin_modification'] = true;
        $this->getSaveProcessor()->processSave($requestData);
    }

    /**
     * Creates response object
     *
     * @param $result
     * @return DataObject
     */
    private function createResponse($result)
    {
        $messages = $this->profileManager->handleMessages();
        $response = new DataObject();
        $response->setData(
            [
                'result' => $result,
                'messages' => $messages,
            ]
        );
        return $response;
    }
}
