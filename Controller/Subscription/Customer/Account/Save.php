<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription\Customer\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Controller\Subscription\AbstractSave;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\Processor\Request as RequestProcessor;
use TNW\Subscriptions\Model\Processor\Response as ResponseProcessor;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as ProfileOrderManager;
use TNW\Subscriptions\Model\SubscriptionProfile\BillingCyclesManager;
use TNW\Subscriptions\Model\ResourceModel\BillingFrequency\CollectionFactory as BillingFrequencyCollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

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
     * @var \TNW\Subscriptions\Model\ResourceModel\BillingFrequency\Collection
     */
    private $billingFrequencyCollection;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

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
     * @param BillingFrequencyCollectionFactory $billingFrequencyCollectionFactory
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
        BillingCyclesManager $billingCyclesManager,
        BillingFrequencyCollectionFactory $billingFrequencyCollectionFactory
    ) {
        $this->profileManager = $profileManager;
        $this->coreRegistry = $coreRegistry;
        $this->responseProcessor = $responseProcessor;
        $this->dataPersistor = $dataPersistor;
        $this->messageManager = $messageManager;
        $this->profileOrderManager = $profileOrderManager;
        $this->billingCyclesManager = $billingCyclesManager;
        $this->billingFrequencyCollection = $billingFrequencyCollectionFactory->create();
        parent::__construct($context, $resultPageFactory, $saveProcessor);
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
                    && ($profile->getBillingFrequencyId()
                        != $request['item_' . $request['objectItemId']]['billing_frequency']
                    );
                $profileDataChanges = $profile->hasDataChanges();
                $profile->setDataChanges(false);

                $errors = $this->processRequestData($request);

                if ($profile->hasDataChanges()) {
                    $this->updateBillingFrequencyUnit($request['item_' . $request['objectItemId']]['billing_frequency']);
                    $profile->setNeedRecollect('1');
                }
                $profile->setDataChanges($profileDataChanges || $profile->hasDataChanges());
                $this->profileManager->saveProfile();
                $currentDate = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
                if ($currentDate < $profile->getStartDate()) {
                    $this->updateNextPaymentDate();
                }

                if ($frequencyChanged) {
                    $this->messageManager->addSuccessMessage(__(
                        'New Billing Frequency will take effect after the next order.'
                    ));
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
     * @inheritdoc
     */
    protected function getJsonResponse(array $errors)
    {
        $request = $this->getRequest()->getParams();
        $response = parent::getJsonResponse($errors);
        $response['data'] = $this->responseProcessor->processResponse($request);

        return $response;
    }

    /**
     * Update unit if it was changed
     *
     * @param $billingFrequencyId
     */
    private function updateBillingFrequencyUnit($billingFrequencyId)
    {
        if ($this->getRequest()->getParam('billing_frequency_id') != $billingFrequencyId) {
            $billingFrequency = $this->billingFrequencyCollection
                ->addFieldToFilter('id', ['eq' => $billingFrequencyId])
                ->fetchItem();
            $profile = $this->profileManager->getProfile();
            $profile->setUnit($billingFrequency->getUnit());
        }
    }

    /**
     * Update ScheduledAt date if was changed date or billing frequency for profile
     *
     * @throws \Exception
     */
    private function updateNextPaymentDate()
    {
        $nowDate = new \DateTime();
        $nowDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);

        $profileSubscription = $this->profileManager->loadProfileFromRequest('subscription_profile_id');
        $this->profileOrderManager->updateNextPaymentDate(
            $profileSubscription,
            $this->calculateStartDate($profileSubscription)
        );
    }

    /**
     * Calculates start date of subscription
     *
     * @param $profileSubscription
     * @return mixed|string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function calculateStartDate($profileSubscription)
    {
        $result = null;

        if ($profileSubscription->getTrialStartDate()) {
            $startDate = new \DateTime($profileSubscription->getTrialStartDate());

            switch ($profileSubscription->getTrialLengthUnit()) {
                case \TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType::DAYS:
                    $intervalUnit = 'D';
                    break;
                case \TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType::MONTHS:
                    $intervalUnit = 'M';
                    break;
                default:
                    throw new \Magento\Framework\Exception\LocalizedException(__('Undefined trial length unit type.'));
            }

            $expression = 'P' . $profileSubscription->getTrialLength() . $intervalUnit;
            $result = $startDate->add(new \DateInterval($expression))
                ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        } else {
            $billingCycles = $this->billingCyclesManager->getBillingCycles(
                $profileSubscription,
                2
            );
            $result = array_pop($billingCycles[0]);
        }

        return $result;
    }
}
