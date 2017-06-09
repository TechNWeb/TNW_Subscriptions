<?php

namespace TNW\Subscriptions\Model\Backend\CreateProfile;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\DataPersistorInterface;

class StepPool
{
    const STEP_PARAM_NAME = 'step';
    const PERSISTOR_STEP_PARAM_NAME = 'tnw_subscription_profile_step';

    const STEP_PARAM_TYPE_CUSTOMER = 'customer';
    const STEP_PARAM_TYPE_STORE = 'store';
    const STEP_PARAM_TYPE_ACCOUNT_INFORMATION = 'account';
    const STEP_PARAM_TYPE_SHIPPING_BILLING = 'shipping_and_billing';
    const STEP_PARAM_TYPE_REVIEW = 'review';

    protected $stepArray = [
        self::STEP_PARAM_TYPE_CUSTOMER,
        self::STEP_PARAM_TYPE_STORE,
        self::STEP_PARAM_TYPE_ACCOUNT_INFORMATION,
        self::STEP_PARAM_TYPE_SHIPPING_BILLING,
        self::STEP_PARAM_TYPE_REVIEW
    ];

    protected $currentStep;
        /** @var DataPersistorInterface */
    protected $dataPersistor;
    /** @var StoreManagerInterface */
    protected $storeManager;

    /**
     * StepPool constructor.
     * @param StoreManagerInterface $storeManager
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DataPersistorInterface $dataPersistor
    ) {
        $this->storeManager = $storeManager;
        $this->dataPersistor = $dataPersistor;
    }


    /**
     * @return array
     */
    public function getStepArray()
    {
        return $this->stepArray;
    }

    /**
     * @return string|null
     */
    public function getCurrentStep()
    {
        //if we have no step param in request, try to get it from session
        return $this->currentStep
            ? $this->currentStep
            : $this->dataPersistor->get(self::PERSISTOR_STEP_PARAM_NAME);
    }

    /**
     * @param string $currentStep
     * @return $this
     */
    public function setCurrentStep($currentStep)
    {
        in_array($currentStep, $this->getStepArray())
            ? $this->currentStep = $currentStep
            : $this->currentStep = self::STEP_PARAM_TYPE_CUSTOMER;

        //set step param in session
        $this->dataPersistor->set(self::PERSISTOR_STEP_PARAM_NAME, $this->currentStep);

        return $this;
    }

    /**
     * @return bool|string
     */
    public function getNextStep()
    {
        $result = false;

        if ($this->getCurrentStep() != self::STEP_PARAM_TYPE_REVIEW) {

            $stepKey = array_search($this->getCurrentStep(), $this->getStepArray());

            if ($stepKey !== false) {
                $result = $this->getStepArray()[$stepKey + 1];
            }

            if ($this->storeManager->isSingleStoreMode() && $result == self::STEP_PARAM_TYPE_STORE){
                $result = self::STEP_PARAM_TYPE_ACCOUNT_INFORMATION;
            }
        }

        return $result;
    }

    /**
     * @return bool|string
     */
    public function getPrevStep()
    {
        $result = false;

        if ($this->getCurrentStep() != self::STEP_PARAM_TYPE_CUSTOMER) {

            $stepKey = array_search($this->getCurrentStep(), $this->getStepArray());

            if ($stepKey !== false) {
                $result = $this->getStepArray()[$stepKey - 1];
            }

            if ($this->storeManager->isSingleStoreMode() && $result == self::STEP_PARAM_TYPE_STORE){
                $result = self::STEP_PARAM_TYPE_CUSTOMER;
            }
        }

        return $result;
    }

    /**
     * @param string $step
     * @return bool
     */
    public function checkStep($step)
    {
        return in_array($step, $this->getStepArray()) ? true : false;
    }
}