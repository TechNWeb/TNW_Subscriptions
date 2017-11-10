<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderInterface;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\Manager as ProductManager;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\Source\ShippingMethods;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\EngineInterface;
use TNW\Subscriptions\Model\SubscriptionProfileFactory;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\UpcomingOrders;

/**
 * Class Manager
 */
class Manager
{
    /**
     * Subscription profile.
     *
     * @var SubscriptionProfileInterface
     */
    private $profile;

    /**
     * Engine for subscription profile processing.
     *
     * @var EngineInterface
     */
    private $engine;

    /**
     * Pool with available engines.
     *
     * @var EnginePool
     */
    private $enginePool;

    /**
     * Repository for saving/retrieving subscription profiles.
     *
     * @var SubscriptionProfileRepository
     */
    private $subscriptionProfileRepository;

    /**
     * Repository for retrieving billing Frequencies.
     *
     * @var BillingFrequencyRepositoryInterface
     */
    private $frequencyRepository;

    /**
     * Factory for creating profile addresses.
     *
     * @var AddressFactory
     */
    private $profileAddressFactory;

    /**
     * Profile product manager.
     *
     * @var ProductManager
     */
    private $productManager;

    /**
     * Profile relation manager.
     *
     * @var OrderRelationManager
     */
    private $orderRelationManager;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SubscriptionProfileFactory
     */
    private $subscriptionProfileFactory;

    /**
     * Quote repository
     *
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * Search criteria builder
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ShippingMethods
     */
    private $shippingMethods;

    /**
     * @var MessageHistoryLogger
     */
    private $historyLogger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PaymentConfig
     */
    private $paymentConfig;

    /**
     * @param EnginePool $enginePool
     * @param SubscriptionProfileRepository $subscriptionProfileRepository
     * @param SubscriptionProfileFactory $subscriptionProfileFactory
     * @param BillingFrequencyRepositoryInterface $frequencyRepository
     * @param DataObjectHelper $dataObjectHelper
     * @param AddressFactory $profileAddressFactory
     * @param ProductManager $productManager
     * @param OrderRelationManager $orderRelationManager
     * @param RequestInterface $request
     * @param CartRepositoryInterface $quoteRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ShippingMethods $shippingMethods
     * @param MessageHistoryLogger $historyLogger
     * @param ScopeConfigInterface $scopeConfig
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(
        EnginePool $enginePool,
        SubscriptionProfileRepository $subscriptionProfileRepository,
        SubscriptionProfileFactory $subscriptionProfileFactory,
        BillingFrequencyRepositoryInterface $frequencyRepository,
        DataObjectHelper $dataObjectHelper,
        AddressFactory $profileAddressFactory,
        ProductManager $productManager,
        OrderRelationManager $orderRelationManager,
        RequestInterface $request,
        CartRepositoryInterface $quoteRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ShippingMethods $shippingMethods,
        MessageHistoryLogger $historyLogger,
        ScopeConfigInterface $scopeConfig,
        PaymentConfig $paymentConfig
    ) {
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->subscriptionProfileFactory = $subscriptionProfileFactory;
        $this->enginePool = $enginePool;
        $this->frequencyRepository = $frequencyRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->profileAddressFactory = $profileAddressFactory;
        $this->productManager = $productManager;
        $this->orderRelationManager = $orderRelationManager;
        $this->request = $request;
        $this->quoteRepository = $quoteRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shippingMethods = $shippingMethods;
        $this->historyLogger = $historyLogger;
        $this->scopeConfig = $scopeConfig;
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * @return SubscriptionProfileInterface
     */
    public function getProfile()
    {
        if (!$this->profile) {
            $this->profile = $this->getEmptyProfile();
        }

        return $this->profile;
    }

    /**
     * @param SubscriptionProfileInterface $profile
     * @return $this
     */
    public function setProfile($profile)
    {
        $this->reset();
        $this->profile = $profile;

        return $this;
    }

    /**
     * Load profile by id
     *
     * @param $profileId
     * @return null|SubscriptionProfileInterface
     */
    public function loadProfile($profileId)
    {
        /** @var SubscriptionProfileInterface $model */
        $model = null;
        if ($profileId) {
            try {
                $model = $this->subscriptionProfileRepository->getById($profileId);
                $this->setProfile($model);
            } catch (\Exception $e) {
                $model = null;
            }
        }

        return $model;
    }

    /**
     * Load profile from request by name
     *
     * @param $requestFieldName
     * @return null|SubscriptionProfileInterface
     */
    public function loadProfileFromRequest($requestFieldName)
    {
        $profileId = (int)$this->request->getParam($requestFieldName, 0);
        return $this->loadProfile($profileId);
    }

    /**
     * Returns empty subscription profile object.
     *
     * @return SubscriptionProfileInterface
     */
    public function getEmptyProfile()
    {
        return $this->subscriptionProfileFactory->create();
    }

    /**
     * Resets internal variables.
     *
     * @return $this
     */
    public function reset()
    {
        $this->profile = null;
        $this->engine = null;

        return $this;
    }

    /**
     * Returns engine for current subscription profile.
     *
     * @return EngineInterface
     */
    public function getEngine()
    {
        if (!$this->engine) {
            $engineCode = $this->getProfile()->getEngineCode();
            /** @var EngineInterface $engine */
            $this->engine = $this->enginePool->getEngineByCode($engineCode);
            $this->engine->setProfile($this->getProfile());
        }

        return $this->engine;
    }

    /**
     * Saves subscription profile.
     *
     * @return SubscriptionProfileInterface
     */
    public function saveProfile()
    {
        $this->setProfile($this->subscriptionProfileRepository->save($this->getProfile()));

        return $this->getProfile();
    }

    /**
     * Sets to profile status "Holded".
     */
    public function setHoldedStatus()
    {
        $this->getProfile()->setStatus(ProfileStatus::STATUS_HOLDED);
        return $this;
    }

    /**
     * Sets to profile status "Active".
     */
    public function setActiveStatus()
    {
        $this->getProfile()->setStatus(ProfileStatus::STATUS_ACTIVE);
        return $this;
    }

    /**
     * Sets to profile status "Suspended".
     */
    public function setSuspendedStatus()
    {
        $this->getProfile()->setStatus(ProfileStatus::STATUS_SUSPENDED);
        return $this;
    }

    /**
     * Sets to profile status "Trial".
     */
    public function setTrialStatus()
    {
        $this->getProfile()->setStatus(ProfileStatus::STATUS_TRIAL);
        return $this;
    }

    /**
     * Sets to profile status "Canceled".
     */
    public function setCanceledStatus()
    {
        $this->getProfile()->setStatus(ProfileStatus::STATUS_CANCELED);
        return $this;
    }

    /**
     * Sets to profile status "Pending".
     */
    public function setPendingStatus()
    {
        $this->getProfile()->setStatus(ProfileStatus::STATUS_PENDING);
        return $this;
    }

    /**
     * Sets to profile status "Complete".
     */
    public function setCompleteStatus()
    {
        $this->getProfile()->setStatus(ProfileStatus::STATUS_COMPLETE);
        return $this;
    }

    /**
     * Sets to profile status "Past Due".
     */
    public function setPastDueStatus()
    {
        $this->getProfile()->setStatus(ProfileStatus::STATUS_PAST_DUE);
        return $this;
    }

    /**
     * Engine profile processing.
     *
     * @param Quote $quote
     * @return OrderInterface
     */
    public function processProfile(Quote $quote)
    {
        return $this->getEngine()->processProfile($quote);
    }

    /**
     * Processes payment method data
     *
     * @param $requestData
     * @return $this
     */
    public function processPaymentMethod($requestData)
    {
        $engine = $this->getEngineFromRequestData($requestData);
        if ($engine) {
            $types = $this->paymentConfig->getCcTypes();
            $additionalInfoOld = $this->getProfile()->getDecodedPaymentAdditionalInfo();
            $oldEngine = $this->getProfile()->getEngineCode();
            $this->getProfile()->setEngineCode($engine);
            $this->getEngine()->processProfileByRequestData($requestData);
            $additionalInfo = $this->getProfile()->getDecodedPaymentAdditionalInfo();
            $ccType = isset($additionalInfo['cc_type']) ? $additionalInfo['cc_type'] : null;
            $ccType = $ccType ? $types[$ccType] : $ccType;
            $ccNumber = isset($additionalInfo['cc_type']) ? $additionalInfo['cc_last_4'] : null;
            $ccExp = "{$this->propertyAdditionalInfo($additionalInfo, 'cc_exp_month')}/{$this->propertyAdditionalInfo($additionalInfo, 'cc_exp_year')}";

            if (strcasecmp($oldEngine, $engine) !== 0) {
                if ($ccType) {
                    $message = __('Payment method changed from <b>%1</b> to <b>%2</b>',
                        $this->scopeConfig->getValue("payment/{$oldEngine}/title"),
                        $this->scopeConfig->getValue("payment/{$engine}/title"));
                    $message .= '<br/>';
                    $message .= __('Credit Card type was added <b>%1</b>', $ccType);
                    $message .= '<br/>';
                    $message .= __('Credit Card number was added <b>%1</b>', sprintf('XXXX%s', $ccNumber));
                } else {
                    $message = __('Payment method changed from <b>%1</b> to <b>%2</b>',
                        $this->scopeConfig->getValue("payment/{$oldEngine}/title"),
                        $this->scopeConfig->getValue("payment/{$engine}/title"));
                }

                $this->historyLogger->log($message, $this->getProfile()->getId());
            } else {
                $ccTypeOld = isset($additionalInfoOld['cc_type']) ? $additionalInfoOld['cc_type'] : null;
                $ccTypeOld = $ccTypeOld ? $types[$ccTypeOld] : $ccTypeOld;
                if (strcasecmp($ccType, $ccTypeOld) !== 0) {
                    $message = __('Card type was changed from <b>%1</b> to <b>%2</b>', $ccTypeOld, $ccType);
                    $this->historyLogger->log($message, $this->getProfile()->getId());
                }
                $ccNumberOld = isset($additionalInfoOld['cc_type']) ? $additionalInfoOld['cc_last_4'] : null;
                if (strcasecmp($ccNumber, $ccNumberOld) !== 0) {
                    $message = __('Credit Card number was changed to <b>%1</b>', sprintf('XXXX%s', $ccNumber));
                    $this->historyLogger->log($message, $this->getProfile()->getId());
                }

                $ccExpOld = "{$this->propertyAdditionalInfo($additionalInfoOld, 'cc_exp_month')}/{$this->propertyAdditionalInfo($additionalInfoOld, 'cc_exp_year')}";
                if (strcasecmp($ccExpOld, $ccExp) !== 0) {
                    $message = __('Exp. Date was changed to <b>%1</b>', $ccExp);
                    $this->historyLogger->log($message, $this->getProfile()->getId());
                }

                $ccVeri = $this->propertyAdditionalInfo($additionalInfo, 'cc_cid');
                $ccVeriOld = $this->propertyAdditionalInfo($additionalInfoOld, 'cc_cid');
                if (strcasecmp($ccVeriOld, $ccVeri) !== 0) {
                    $message = __('Card Verification Number was changed');
                    $this->historyLogger->log($message, $this->getProfile()->getId());
                }
            }
        }

        return $this;
    }

    /**
     * @param $additionalInfo
     * @param $property
     * @return mixed|null
     */
    private function propertyAdditionalInfo($additionalInfo, $property)
    {
        if (empty($additionalInfo)) {
            return null;
        }

        if (is_string($additionalInfo)) {
            $additionalInfo = (array)json_decode($additionalInfo);
        }

        if (empty($additionalInfo[$property])) {
            return null;
        }

        return $additionalInfo[$property];
    }

    /**
     * Processes shipping method data
     *
     * @param $requestData
     * @return $this
     */
    public function processShippingMethod($requestData)
    {
        $shippingMethod = $this->getShippingMethodFromRequestData($requestData);
        if ($shippingMethod) {
            $this->getProfile()->setShippingMethod($shippingMethod);
            $quote = $this->getNextQuote();
            $shippingDescription = '';
            if ($quote) {
                $shippingMethodOptions = $this->shippingMethods->getShippingMethodOptions($quote, false);
                foreach ($shippingMethodOptions as $shippingMethodOption) {
                    if ($shippingMethodOption['value'] == $shippingMethod) {
                        $shippingDescription = $shippingMethodOption['label'];
                    }
                }
            }

            $oldShippingDescription = $this->getProfile()->getShippingDescription();
            $this->getProfile()->setShippingDescription($shippingDescription);

            if(strcasecmp($oldShippingDescription, $shippingDescription) !== 0) {
                $message = __('Shipping method changed from <b>%1</b> to <b>%2</b>', $oldShippingDescription, $shippingDescription);
                $this->historyLogger->log($message, $this->getProfile()->getId());
            }
        }
        return $this;
    }

    /**
     * Assigns order to profile.
     *
     * @param SubscriptionProfileOrderInterface $relation
     * @param OrderInterface $order
     * @return null|SubscriptionProfileOrderInterface
     */
    public function assignOrderToProfile(
        SubscriptionProfileOrderInterface $relation,
        OrderInterface $order
    ) {
        $relation->setMagentoOrderId($order->getId());
        return $this->orderRelationManager->saveRelation($relation);
    }

    /**
     * Assigns quote to profile.
     *
     * @param Quote $quote
     * @param SubscriptionProfileInterface $profile
     * @param null|string $date
     * @return null|SubscriptionProfileOrderInterface
     */
    public function assignQuoteToProfile(
        Quote $quote,
        SubscriptionProfileInterface $profile,
        $date = null
    ) {
        if (!$date) {
            $date = new \DateTime();
            $date = $date->format('Y-m-d H:i:s');
        }

        $relation = $this->orderRelationManager
            ->getNewProfileOrderRelation()
            ->setSubscriptionProfileId($profile->getId())
            ->setMagentoQuoteId($quote->getId())
            ->setScheduledAt($date);
        return $this->orderRelationManager->saveRelation($relation);
    }

    /**
     * Set data to profile from quote.
     *
     * @param Quote $quote
     * @param null|\DateTime $date
     * @return $this
     * @throws \Exception
     */
    public function populateProfileData(Quote $quote, $date = null)
    {
        $request = $this->getUniqueBuyRequest($quote);

        if (!empty($request)) {
            $frequency = $this->frequencyRepository->getById($request['billing_frequency']);
            if (!$frequency || !$frequency->getId()) {
                throw new \Exception(__('Can not create profile with empty frequency.'));
            }
        }

        if (isset($frequency)) {
            $startDate = $this->getFullStartDate($request['start_on'], $date);
            $this->getProfile()
                ->setCustomerId($quote->getCustomerId())
                ->setWebsiteId($quote->getStore()->getWebsiteId())
                ->setEngineCode($quote->getPayment()->getMethod())
                ->setShippingMethod($quote->getShippingAddress()->getShippingMethod())
                ->setShippingDescription($quote->getShippingAddress()->getShippingDescription())
                ->setIsVirtual($quote->getIsVirtual())
                ->setProfileCurrencyCode($quote->getQuoteCurrencyCode())
                ->setTerm($request['term'])
                ->setTotalBillingCycles(!$request['term'] ? $request['period'] : 0)
                ->setStartDate($startDate)
                ->setBillingFrequencyId($frequency->getId())
                ->setFrequency($frequency->getFrequency())
                ->setUnit($frequency->getUnit())
                ->setStatus(ProfileStatus::STATUS_PENDING)
                ->setTrialStartDate(null)
                ->setTrialLength($request['trial_period'])
                ->setTrialLengthUnit($request['trial_unit_id'])
                ->setGenerateQuotesState(SubscriptionProfile::GENERATE_QUOTES_STATE_NEED_GENERATE);

            if ($request['is_trial']) {
                $this->getProfile()->setTrialStartDate($startDate);
                $this->getProfile()->setStartDate($this->calculateStartDate());
                $this->getProfile()->setStatus(ProfileStatus::STATUS_TRIAL);
            }

            $this->getProfile()->setAddresses(
                $this->populateAddressesData($quote)
            );
            $this->getProfile()->setProducts(
                $this->populateProductsData($quote->getAllItems())
            );
        }

        return $this;
    }

    /**
     * Sets payment information for a profile depending on the engine code.
     *
     * @param Payment $payment
     * @return $this
     */
    public function populatePaymentData(Payment $payment)
    {
        $data = $this->getEngine()->getProfilePaymentInfo($payment);
        foreach ($data as $key => $value) {
            $method = 'set' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($key);
            $this->getProfile()->$method($value);
        }

        return $this;
    }

    /**
     * Returns next profile relation
     *
     * @return null|SubscriptionProfileOrderInterface
     */
    public function getNextProfileRelation()
    {
        return $this->orderRelationManager->getNextProfileRelation($this->getProfile());
    }

    /**
     * Returns next profile relation
     *
     * @return null|Quote
     */
    public function getNextQuote()
    {
        $quote = null;
        $nextProfileRelation =  $this->getNextProfileRelation();
        if ($nextProfileRelation) {
            $quoteId = $nextProfileRelation->getMagentoQuoteId();
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(Quote::KEY_ENTITY_ID, $quoteId)->create();
            $quotes = $this->quoteRepository->getList($searchCriteria)->getItems();
            if (count($quotes)) {
                $quote = reset($quotes);
            }
        }
        return $quote;
    }

    /**
     * Handles messages for subscription edit form
     *
     * @return array
     */
    public function handleMessages()
    {
        $messages = [];
        /** @var SubscriptionProfile $profile */
        $profile = $this->getProfile();
        if ($profile->getNeedRecollect()) {
            $messages[] = [
                'index' => 'index = subscription_details_message',
                'message' => $profile->getShippingBillingChangesMadeMessageForSubscriptionDetails()
            ];
        }
        if ($profile->getProductNeedRecollect()) {
            $messages[] = [
                'index' => 'name = tnw_subscriptionprofile_form.areas.' . UpcomingOrders::GROUP_UPCOMING_ORDERS,
                'message' => $profile->getShippingBillingChangesMadeMessageForUpcomingOrders()
            ];
            $messages[] = [
                'index' => 'index = profit_message',
                'message' => $profile->getProductChangesMadeMessageForProfit()
            ];
        }
        return $messages;
    }

    /**
     * Returns list of profile addresses created from billing and shipping addresses.
     *
     * @param Quote $quote
     * @return array
     */
    private function populateAddressesData(Quote $quote)
    {
        /** @var SubscriptionProfileAddressInterface $profileBillingAddress */
        $profileBilling = $this->profileAddressFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $profileBilling,
            $quote->getBillingAddress()->toArray(),
            SubscriptionProfileAddressInterface::class
        );
        /** @var SubscriptionProfileAddressInterface $profileShipping */
        $profileShipping = $this->profileAddressFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $profileShipping,
            $quote->getShippingAddress()->toArray(),
            SubscriptionProfileAddressInterface::class
        );

        return [
            $profileBilling,
            $profileShipping
        ];
    }

    /**
     * Returns unique subscription data from  buy request.
     *
     * @param Quote $quote
     * @return array|null
     */
    private function getUniqueBuyRequest(Quote $quote)
    {
        $result = null;
        $items = $quote->getAllItems();

        if ($items) {
            /** @var Item $item */
            $item = reset($items);
            $result = $item->getBuyRequest()->getDataByPath(
                Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME . DIRECTORY_SEPARATOR . Create::UNIQUE
            );
        }

        return $result;
    }

    /**
     * Returns list of profile products created from quote items.
     *
     * @param Item[] $items
     * @return array
     */
    private function populateProductsData($items)
    {
        $profileProducts = [];
        /** @var Item $item */
        foreach ($items as $item) {
            $profileProducts[] = $this->productManager->reset()
                ->populateProductDataFromQuoteItem($item)
                ->getProfileProduct();
        }

        return $profileProducts;
    }

    /**
     * Calculates start date of subscription when trial period is set.
     *
     * @return null|string
     * @throws \Exception
     */
    private function calculateStartDate()
    {
        $result = null;

        if ($this->getProfile()->getTrialStartDate()) {
            $startDate = new \DateTime($this->getProfile()->getTrialStartDate());

            switch ($this->getProfile()->getTrialLengthUnit()) {
                case TrialLengthUnitType::DAYS:
                    $intervalUnit = 'D';
                    break;
                case TrialLengthUnitType::MONTHS:
                    $intervalUnit = 'M';
                    break;
                default:
                    throw new \Exception('Undefined trial length unit type.');
                    break;
            }

            $expression = 'P' . $this->getProfile()->getTrialLength() . $intervalUnit;
            $result = $startDate->add(new \DateInterval($expression))
                ->format('Y-m-d H:i:s');
        }

        return $result;
    }

    /**
     * Returns full start date.
     *
     * @param string $startOn
     * @param null|\DateTime $date
     * @return string
     */
    private function getFullStartDate($startOn, $date = null)
    {
        if (!$date) {
            $date = new \DateTime();
        }
        $startDate = new \DateTime($startOn);
        $diff = $date->diff($startDate, true);
        //Add hours, minutes, and seconds to start date
        $expression = 'PT' . $diff->h . 'H' . $diff->i . 'M' . $diff->s . 'S';
        $startDate->add(new \DateInterval($expression));

        return $startDate->format('Y-m-d H:i:s');
    }

    /**
     * Returns engine code form request data
     *
     * @param array $requestData
     * @return int|null|string
     */
    public function getEngineFromRequestData(array $requestData)
    {
        $engine = null;
        $paymentPostData = isset($requestData['payment']) ? $requestData['payment'] :[];
        foreach ($paymentPostData as $code => $methodData) {
            if ($methodData['method']) {
                $engine = $code;
                break;
            }
        }
        return $engine;
    }

    /**
     * Returns shipping method code form request data
     *
     * @param array $requestData
     * @return int|null|string
     */
    public function getShippingMethodFromRequestData(array $requestData)
    {
        $shippingMethodCode= isset($requestData['shipping_method_id'])
            ? $requestData['shipping_method_id']
            : null;
        return $shippingMethodCode;
    }
}
