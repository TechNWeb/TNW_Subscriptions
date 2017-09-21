<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Edit;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Model\SubscriptionProfile\Address\Manager as ProfileAddressManager;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Class Save
 */
class Save extends Action
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
     * Subscription profile address manager
     *
     * @var ProfileAddressManager
     */
    private $profileAddressManager;

    /**
     * Save constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JsonFactory $jsonFactory
     * @param ProfileManager $profileManager
     * @param ProfileAddressManager $profileAddressManager
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JsonFactory $jsonFactory,
        ProfileManager $profileManager,
        ProfileAddressManager $profileAddressManager
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->jsonFactory = $jsonFactory;
        $this->profileManager = $profileManager;
        $this->profileAddressManager = $profileAddressManager;

        parent::__construct($context);
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
                $this->processRequestData();
                $this->profileManager->saveProfile();
            } catch (\Exception $e) {
                $result = false;
            }
        }

        $response = new DataObject();
        $response->setData('result', $result);

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

        $this->profileAddressManager->processShippingAddress($requestData);
        $this->profileAddressManager->processBillingAddress($requestData);
        $this->profileManager->processPaymentMethod($requestData);
        $this->profileManager->processShippingMethod($requestData);
    }
}
