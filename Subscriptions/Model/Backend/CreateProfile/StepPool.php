<?php

namespace TNW\Subscriptions\Model\Backend\CreateProfile;

use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\Website;
use TNW\Subscriptions\Model\Backend\Session\Quote;

class StepPool
{
    const STEP_PARAM_NAME = 'step';
    const PERSISTOR_STEP_PARAM_NAME = 'tnw_subscription_profile_step';

    const STEP_PARAM_TYPE_CUSTOMER = 'customer';
    const STEP_PARAM_TYPE_STORE = 'store';
    const STEP_PARAM_TYPE_ACCOUNT_INFORMATION = 'account';
    const STEP_PARAM_TYPE_SHIPPING_BILLING = 'payment_and_billing';
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
    /** @var  ObjectManagerInterface */
    private $_objectManager;
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $_customerRepository;

    /**
     * StepPool constructor.
     * @param StoreManagerInterface $storeManager
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DataPersistorInterface $dataPersistor,
        ObjectManagerInterface $objectManager
    ) {
        $this->storeManager = $storeManager;
        $this->dataPersistor = $dataPersistor;
        $this->_objectManager = $objectManager;
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
        $this->clearCustomer($currentStep);

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

    /**
     * Returns current step's title.
     *
     * @return string
     */
    public function getCurrentStepTitle()
    {
        $title = '';
        /** @var \TNW\Subscriptions\Model\Backend\Session\Quote $session */
        $session = $this->_getSession();

        if ($session->getCustomerId()) {
            $customerName = $this->_getCustomerName($session->getCustomerId());
            $title .= ' ' . sprintf(__('for %s'), $customerName);
        } elseif ($this->getCurrentStep() != self::STEP_PARAM_TYPE_CUSTOMER && $session->getCreateNewCustomer()) {
            $title .= ' ' . __('for a New Customer');
        }

        /** @var \Magento\Store\Api\Data\StoreInterface|Store $store */
        $store = $session->getStore();
        if ($store && $store->getId()) {
            /** @var Website $website */
            $website = $store->getWebsite();
            $websiteName = $website->getName();
            $title .= ' ' . sprintf(__('in %s'), $websiteName);
        }

        return $title;
    }

    /**
     * Returns current session.
     *
     * @return \TNW\Subscriptions\Model\Backend\Session\Quote
     */
    private function _getSession()
    {
        return $this->_objectManager->get(Quote::class);
    }

    /**
     * Returns customer first name and last name from customer model.
     *
     * @param $customerId
     * @return string
     */
    private function _getCustomerName($customerId)
    {
        $customerName = '';
        if ($customerId) {
            $customer = $this->_getCustomerRepository()->getById($customerId);
            if ($customer->getId()) {
                $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
            }
        }

        return $customerName;
    }

    /**
     * Returns customer repository object.
     *
     * @return \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private function _getCustomerRepository()
    {
        if (!$this->_customerRepository) {
            $this->_customerRepository= $this->_objectManager->create(
                \Magento\Customer\Api\CustomerRepositoryInterface::class
            );
        }

        return $this->_customerRepository;
    }

    /**
     * Cleares customer data from session.
     *
     * @param $currentStep
     */
    private function clearCustomer($currentStep)
    {
        if ($currentStep == self::STEP_PARAM_TYPE_CUSTOMER) {
            $this->_getSession()->setCustomerId(null);
        }
    }
}
