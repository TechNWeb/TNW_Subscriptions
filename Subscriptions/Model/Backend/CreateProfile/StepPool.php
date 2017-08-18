<?php

namespace TNW\Subscriptions\Model\Backend\CreateProfile;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use TNW\Subscriptions\Model\Backend\Session\Quote;
use Magento\Store\Model\Store;

/**
 * Class StepPool
 */
class StepPool
{
    /**#@+
     * Request and session param names
     */
    const STEP_PARAM_NAME = 'step';
    const PERSISTOR_STEP_PARAM_NAME = 'tnw_subscription_profile_step';
    /**#@-*/

    /**#@+
     * Constants for subscription profile creation steps
     */
    const STEP_PARAM_TYPE_CUSTOMER = 'customer';
    const STEP_PARAM_TYPE_STORE = 'store';
    const STEP_PARAM_TYPE_ACCOUNT_INFORMATION = 'account';
    const STEP_PARAM_TYPE_SHIPPING_BILLING = 'shipping_and_billing';
    const STEP_PARAM_TYPE_PAYMENT= 'payment';
    /**#@-*/

    /**
     * List of subscription profile creation steps
     *
     * @var array
     */
    private $stepArray = [
        self::STEP_PARAM_TYPE_CUSTOMER,
        self::STEP_PARAM_TYPE_STORE,
        self::STEP_PARAM_TYPE_ACCOUNT_INFORMATION,
        self::STEP_PARAM_TYPE_SHIPPING_BILLING,
        self::STEP_PARAM_TYPE_PAYMENT
    ];

    /**
     * @var string
     */
    private $currentStep;

    /**
     * Data Persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * Store manager.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Admin session.
     *
     * @var Quote
     */
    private $session;

    /**
     * Repository for retrieving customers.
     *
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * StepPool constructor.
     * @param StoreManagerInterface $storeManager
     * @param DataPersistorInterface $dataPersistor
     * @param Quote $session
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DataPersistorInterface $dataPersistor,
        Quote $session,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->storeManager = $storeManager;
        $this->dataPersistor = $dataPersistor;
        $this->session = $session;
        $this->customerRepository = $customerRepository;
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
        in_array($currentStep, $this->getStepArray(), true)
            ? $this->currentStep = $currentStep
            : $this->currentStep = self::STEP_PARAM_TYPE_CUSTOMER;

        //set step param in session
        $this->dataPersistor->set(self::PERSISTOR_STEP_PARAM_NAME, $this->currentStep);
        $this->clearCustomer($currentStep);

        return $this;
    }

    /**
     * Returns next step.
     *
     * @return bool|string
     */
    public function getNextStep()
    {
        $result = false;

        if ($this->getCurrentStep() !== self::STEP_PARAM_TYPE_PAYMENT) {

            $stepKey = array_search($this->getCurrentStep(), $this->getStepArray());

            if ($stepKey !== false) {
                $result = $this->getStepArray()[$stepKey + 1];
            }

            if ($this->storeManager->hasSingleStore() && $result == self::STEP_PARAM_TYPE_STORE) {
                $result = self::STEP_PARAM_TYPE_ACCOUNT_INFORMATION;
            }
        }

        return $result;
    }

    /**
     * Returns previous step.
     *
     * @return bool|string
     */
    public function getPrevStep()
    {
        $result = false;

        if ($this->getCurrentStep() !== self::STEP_PARAM_TYPE_CUSTOMER) {

            $stepKey = array_search($this->getCurrentStep(), $this->getStepArray());

            if ($stepKey !== false) {
                $result = $this->getStepArray()[$stepKey - 1];
            }

            if ($this->storeManager->isSingleStoreMode() && $result == self::STEP_PARAM_TYPE_STORE) {
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

        if ($this->session->getCustomerId()) {
            $customerName = $this->getCustomerName($this->session->getCustomerId());
            $title .= ' ' . sprintf(__('for %s'), $customerName);
        } elseif ($this->getCurrentStep() !== self::STEP_PARAM_TYPE_CUSTOMER && $this->session->getCreateNewCustomer()) {
            $title .= ' ' . __('for a new customer');
        }

        /** @var Store $store */
        $store = $this->session->getStore();
        if ($store && $store->getId()) {
            /** @var Website $website */
            $website = $store->getWebsite();
            $websiteName = $website->getName();
            $title .= ' ' . sprintf(__('in %s'), $websiteName);
        }

        return $title;
    }

    /**
     * Returns customer first name and last name from customer model.
     *
     * @param int $customerId
     * @return string
     */
    private function getCustomerName($customerId)
    {
        $customerName = '';
        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            if ($customer->getId()) {
                $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
            }
        }

        return $customerName;
    }

    /**
     * Cleares customer data from session.
     *
     * @param string $currentStep
     */
    private function clearCustomer($currentStep)
    {
        if ($currentStep == self::STEP_PARAM_TYPE_CUSTOMER) {
            $this->session->setCustomerId(null);
        }
    }
}
