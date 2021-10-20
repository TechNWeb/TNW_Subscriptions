<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription\Customer\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Model\Processor\Request as RequestProcessor;
use TNW\Subscriptions\Model\Processor\Response as ResponseProcessor;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\BillingCyclesManager;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as ProfileOrderManager;
use Magento\Framework\App\Action;

/**
 * Saves modified subscription profile.
 */
class Save extends Action\Action
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

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var ProfileOrderManager
     */
    private $profileOrderManager;

    /**
     * @var BillingCyclesManager
     */
    private $billingCyclesManager;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

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
     * @param ProfileManager $profileManager
     * @param Registry $coreRegistry
     * @param ResponseProcessor $responseProcessor
     * @param DataPersistorInterface $dataPersistor
     * @param ManagerInterface $messageManager
     * @param ProfileOrderManager $profileOrderManager
     * @param BillingCyclesManager $billingCyclesManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        RequestProcessor $saveProcessor,
        ProfileManager $profileManager,
        Registry $coreRegistry,
        ResponseProcessor $responseProcessor,
        DataPersistorInterface $dataPersistor,
        ManagerInterface $messageManager,
        ProfileOrderManager $profileOrderManager,
        BillingCyclesManager $billingCyclesManager
    ) {
        $this->profileManager = $profileManager;
        $this->coreRegistry = $coreRegistry;
        $this->responseProcessor = $responseProcessor;
        $this->dataPersistor = $dataPersistor;
        $this->messageManager = $messageManager;
        $this->profileOrderManager = $profileOrderManager;
        $this->billingCyclesManager = $billingCyclesManager;
        $this->resultPageFactory = $resultPageFactory;
        $this->saveProcessor = $saveProcessor;
        parent::__construct($context);
    }

    /**
     * Execute save profile data on customer account page.
     *
     * @return mixed
     */
    public function execute()
    {
        $errors = [];
        $request = $this->getRequest()->getParams();
        if (isset($request['objectItemId'])
            && isset($request['item_' . $request['objectItemId']]['term'])
            && (1 == $request['item_' . $request['objectItemId']]['term'])
        ) {
            $request['item_' . $request['objectItemId']]['period'] = 0;
        }
        $result = $this->initProfile();
        if ($result) {
            try {
                /** @var SubscriptionProfile $profile */
                $profile = $this->profileManager->getProfile();
                $frequencyChanged = isset($request['objectItemId'])
                    && isset($request['item_' . $request['objectItemId']]['billing_frequency'])
                    && (
                        $profile->getBillingFrequencyId()
                        != $request['item_' . $request['objectItemId']]['billing_frequency']
                    );
                $trialLength = $profile->getTrialLength();
                $originalStartDate = $trialLength
                    ? date('Y-m-d', strtotime($profile->getTrialStartDate()))
                    : date('Y-m-d', strtotime($profile->getOriginalStartDate()));
                $startOnChanged = isset($request['objectItemId'])
                    && isset($request['item_' . $request['objectItemId']]['start_on'])
                    && (
                        date('Y-m-d', strtotime($request['item_' . $request['objectItemId']]['start_on']))
                        !== $originalStartDate
                    );
                if ($startOnChanged) {
                    $originalStartDate = date(
                        'Y-m-d',
                        strtotime($request['item_' . $request['objectItemId']]['start_on'])
                    );
                }
                $profileDataChanges = $profile->hasDataChanges();
                $profile->setDataChanges(false);

                if ($request['next_payment_date_value'] <= date('m/d/Y', time())) {
                    $errors[] = __('Only future dates are allowed for Next Payment Date');
                } else {
                    $errors = $this->processRequestData($request);
                }

                if (!$errors) {
                    if ($profile->hasDataChanges()) {
                        $profile->setNeedRecollect('1');
                    }
                    $profile->setDataChanges($profileDataChanges || $profile->hasDataChanges());
                    $this->profileManager->saveProfile();
                    if ((strtotime(date('Y-m-d')) < strtotime($originalStartDate))
                        && ($frequencyChanged || $startOnChanged)
                    ) {
                        $nextDate = $trialLength
                            ? $profile->getStartDate()
                            : $this->billingCyclesManager->calculateBillingCycleDate($profile);
                        $this->profileOrderManager->updateNextPaymentDate($profile, $nextDate);
                    }
                    if ($frequencyChanged) {
                        $this->messageManager->addSuccessMessage(__(
                            'New Billing Frequency will take effect after the next order.'
                        ));
                    }
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
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

    /**
     * Init profile from request.
     *
     * @return bool
     */
    private function initProfile()
    {
        $result = false;

        /** @var SubscriptionProfile $model */
        $model = $this->profileManager->loadProfileFromRequest('entity_id');

        if ($this->dataPersistor->get('editProfileId')) {
            $model = $this->profileManager->loadProfile($this->dataPersistor->get('editProfileId'));
        }
        if ($model) {
            $result = true;
            $this->coreRegistry->register('tnw_subscription_profile', $model, true);
        }

        return $result;
    }

    /**
     * @param array $errors
     * @return array
     */
    protected function getJsonResponse(array $errors)
    {
        $request = $this->getRequest()->getParams();
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
        $result['data'] = $this->responseProcessor->processResponse($request);
        return $result;
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
}
