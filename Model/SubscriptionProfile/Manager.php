<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
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
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;
use Magento\Framework\DataObject;
use TNW\Subscriptions\Model\Config\Source\ShippingFallback;
use Magento\Quote\Model\QuoteFactory;
use TNW\Subscriptions\Model\Config\Source\FreeShipping;

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
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

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
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\Queue
     */
    private $resourceQueue;

    /**
     * @var ProfileStatus
     */
    private $profileStatus;

    /**
     * @var DataObject\Factory
     */
    private $dataObjectFactory;

    /**
     * @var \TNW\Subscriptions\Model\Config
     */
    private $mpowerConfig;

    /**
     * @var \TNW\Subscriptions\Model\Shipping\Free
     */
    private $freeShipping;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var bool
     */
    private $tempQuote = false;

    /**
     * @var Quote\TotalsCollector
     */
    private $totalsCollector;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Manager constructor.
     * @param EnginePool $enginePool
     * @param SubscriptionProfileRepository $subscriptionProfileRepository
     * @param PaymentRepository $paymentRepository
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
     * @param \TNW\Subscriptions\Model\ResourceModel\Queue $resourceQueue
     * @param ProfileStatus $profileStatus
     * @param DataObject\Factory $dataObjectFactory
     * @param \TNW\Subscriptions\Model\Config $mpowerConfig
     * @param \TNW\Subscriptions\Model\Shipping\Free $freeShipping
     * @param QuoteFactory $quoteFactory
     * @param Quote\TotalsCollector $totalsCollector
     */
    public function __construct(
        EnginePool $enginePool,
        SubscriptionProfileRepository $subscriptionProfileRepository,
        PaymentRepository $paymentRepository,
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
        PaymentConfig $paymentConfig,
        \TNW\Subscriptions\Model\ResourceModel\Queue $resourceQueue,
        ProfileStatus $profileStatus,
        \Magento\Framework\DataObject\Factory $dataObjectFactory,
        \TNW\Subscriptions\Model\Config $mpowerConfig,
        \TNW\Subscriptions\Model\Shipping\Free $freeShipping,
        QuoteFactory $quoteFactory,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        SerializerInterface $serializer
    ) {
        $this->totalsCollector = $totalsCollector;
        $this->quoteFactory = $quoteFactory;
        $this->freeShipping = $freeShipping;
        $this->mpowerConfig = $mpowerConfig;
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
        $this->paymentRepository = $paymentRepository;
        $this->resourceQueue = $resourceQueue;
        $this->profileStatus = $profileStatus;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->serializer = $serializer;
    }

    /**
     * @param $profile
     * @param $quote
     */
    public function populateTotals($profile, $quote)
    {
        $totals = $quote->getTotals();
        if ($profile->getStatus() == ProfileStatus::STATUS_TRIAL) {
            $quoteItem = $quote->getItemsCollection()->getFirstItem();
            $price = $quoteItem->getPrice() * $quoteItem->getQty();
            $totals['grand_total']->setValue(
                $totals['grand_total']->getValue() - $totals['subtotal']->getValue() + $price
            );
            $totals['subtotal']->setValue($price);
        }

        foreach ($totals as $total) {
            $profile->setData($total->getCode(), $total->getValue());
        }
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
            $engineCode = $this->getProfile()->getPayment()->getEngineCode();
            /** @var EngineInterface $engine */
            $this->engine = $this->enginePool->getEngineByCode($engineCode);
            $this->engine->setProfile($this->getProfile());
        }

        return $this->engine;
    }

    /**
     * @return SubscriptionProfileInterface
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveProfile()
    {
        $profile = $this->getProfile();
        $profilePayment = $profile->getPayment();
        if ($profilePayment->dataHasChangedFor('payment_additional_info') && $profilePayment->getSentMail() != 0) {
            $profilePayment->setSentMail(0);
        }

        $this->tempQuote = true;
        $quote = $this->quoteFactory->create(['data' => ['is_active' => false]])
            ->assignCustomer($profile->getCustomer());
        $this->populateQuoteData($quote, $profile, true, true);
        $this->populateTotals($profile, $quote);
        $this->tempQuote = false;

        $this->subscriptionProfileRepository->save($profile);

        $payment = $profile->getPayment()
            ->setProfileId($profile->getId());
        $this->paymentRepository->save($payment);

        return $profile;
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
            $additionalInfoOld = $this->getProfile()->getPayment()->getDecodedPaymentAdditionalInfo();
            $oldEngine = $this->getProfile()->getPayment()->getEngineCode();
            $this->getProfile()->getPayment()->setEngineCode($engine);
            $this->getEngine()->processProfileByRequestData($requestData);
            $additionalInfo = $this->getProfile()->getPayment()->getDecodedPaymentAdditionalInfo();
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

            if (strcasecmp($oldShippingDescription, $shippingDescription) !== 0) {
                $message = __('Shipping method changed from <b>%1</b> to <b>%2</b>', $oldShippingDescription,
                    $shippingDescription);
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
     * @return SubscriptionProfileOrderInterface
     * @throws LocalizedException
     */
    public function assignQuoteToProfile(
        Quote $quote,
        SubscriptionProfileInterface $profile,
        $date = null
    ) {
        if (!$date) {
            $date = date_create()->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        }

        $relation = $this->orderRelationManager
            ->getNewProfileOrderRelation()
            ->setSubscriptionProfileId($profile->getId())
            ->setMagentoQuoteId($quote->getId())
            ->setScheduledAt($date);
        return $this->orderRelationManager->saveRelation($relation);
    }

    /**
     * @param Quote $quote
     * @param $quoteItems
     * @param null $date
     * @return $this
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function populateProfileData(Quote $quote, $quoteItems, $date = null)
    {
        $request = $this->getUniqueBuyRequest($quoteItems);
        if (empty($request)) {
            return $this;
        }

        $frequency = $this->frequencyRepository->getById($request['billing_frequency']);
        $startDate = $this->getFullStartDate($request['start_on'], $date);

        $this->getProfile()
            ->setCustomerId($quote->getCustomerId())
            ->setWebsiteId($quote->getStore()->getWebsiteId())
            ->setShippingMethod($quote->getShippingAddress()->getShippingMethod())
            ->setShippingDescription($quote->getShippingAddress()->getShippingDescription())
            ->setIsVirtual($this->isQuoteHasVirtualProducts($quoteItems))
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

        $this->getProfile()->getPayment()
            ->setEngineCode($quote->getPayment()->getMethod());

        //set trial start date to profile
        if ($request['is_trial']) {
            $this->getProfile()
                ->setTrialStartDate($startDate)
                ->setStartDate($this->calculateStartDate());

            //set status "trial" if trial period starts immediately
            if (strtotime($startDate) <= time()) {
                $this->getProfile()->setStatus(ProfileStatus::STATUS_TRIAL);
            }
        }

        $profileProducts = $this->productManager->populateProductsData($quoteItems);
        $profileChildProducts = $this->productManager->populateChildProductsData($quoteItems, $profileProducts);
        $profileAddress = $this->populateAddressesData($quote);

        $this->getProfile()
            ->setAddresses($profileAddress)
            ->setProducts(array_merge($profileProducts, $profileChildProducts));

        return $this;
    }

    /**
     * @param \Magento\Framework\DataObject $requestData
     * @param \Magento\Catalog\Model\Product $product
     * @throws LocalizedException
     */
    public function addProduct($requestData, $product)
    {
        /** @var \Magento\Catalog\Model\Product[] $cartCandidates */
        $cartCandidates = $product->getTypeInstance()
            ->prepareForCartAdvanced($requestData, $product);

        /**
         * Error message
         */
        if (is_string($cartCandidates) || $cartCandidates instanceof \Magento\Framework\Phrase) {
            throw new LocalizedException(__((string)$cartCandidates));
        }

        /**
         * If prepare process return one object
         */
        if (!is_array($cartCandidates)) {
            $cartCandidates = [$cartCandidates];
        }

        $profileProducts = $this->getProfile()->getProducts();

        $productObject = new DataObject($cartCandidates[0]->getData());

        $parentItem = null;
        foreach ($cartCandidates as $candidate) {
            $productObject
                ->setData('name', $candidate->getName())
                ->setData('sku', $candidate->getData('sku'))
                ->setData('entity_id', $candidate->getId());

            $item = $this->productManager->reset()
                ->populateProductDataFromCartCandidate(
                    $candidate,
                    $requestData,
                    $productObject,
                    null !== $parentItem
                )
                ->getProfileProduct();

            /**
             * As parent item we should always use the item of first added product
             */
            if (!$parentItem) {
                $parentItem = $item;
            }
            if ($parentItem && $candidate->getParentProductId() && !$item->getParentId()) {
                $children = $parentItem->getChildren();
                $children[] = $item;

                $parentItem->setChildren($children);
            }

            $profileProducts[] = $item;
        }

        $this->historyLogger->log(
            __(
                '<a href="{productUrl|%1}" target="_blank">%2</a> product added',
                $cartCandidates[0]->getId(),
                $cartCandidates[0]->getName()
            ),
            $this->getProfile()->getId()
        );

        $this->getProfile()->setProducts($profileProducts);
    }

    /**
     * @param Quote $quote
     * @param SubscriptionProfileInterface $profile
     * @param bool $collectQuoteTotals
     * @param bool $isReBill
     * @throws LocalizedException
     */
    public function populateQuoteData(
        Quote $quote,
        SubscriptionProfileInterface $profile,
        $collectQuoteTotals = true,
        $isReBill = false
    ) {
        if (!$quote->getId() && !$this->tempQuote) {
            throw new LocalizedException(__('Quote not saved'));
        }

        $outOfStockProducts = [];
        $this->setProfile($profile);

        //Deactivate quote
        $quote->setIsActive(false);
        $quote->setData('ignore_old_qty', true);
        $quote->setData('is_super_mode', true);
        $quote->setData('scheduled', true);

        //Set store
        $quote->setStore(
            $profile->getWebsite()->getDefaultStore()
        );

        //Set currency
        $quote->setQuoteCurrencyCode($profile->getProfileCurrencyCode());

        //Set customer
        if (!$quote->getCustomerId()) {
            $quote->assignCustomer($profile->getCustomer());
        }

        //Add products
        foreach ($profile->getVisibleProducts() as $profileProduct) {
            $magentoProduct = $profileProduct->getMagentoProduct();
            if ($isReBill) {
                $profileProduct->setTrialStatus(0);
                $magentoProduct->setTnwSubscrTrialStatus(0);
            }
            $quoteItemCreated = true;
            try {
                $quoteItem = $quote->addProduct(
                    $magentoProduct,
                    $this->getProductAddRequest($profileProduct, $isReBill)
                );
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $quoteItemCreated = false;
                $outOfStockProducts[$magentoProduct->getId()] = $magentoProduct->getName();
            }
            if ($quoteItemCreated) {
                if (\is_string($quoteItem)) {
                    throw new LocalizedException(__($quoteItem));
                }

                $profileItemIds = $quoteItem->getData('profile_item_ids');
                $profileItemIds[] = $profileProduct->getId();

                $quoteItem->setData('profile_item_ids', array_unique($profileItemIds));
            }
        }
        if (count($quote->getAllVisibleItems())) {
            if ($collectQuoteTotals) {
                $profileBillingAddressData = $profile->getBillingAddress()->getData();
                unset($profileBillingAddressData['id']);
                $profileBillingAddressData = array_filter($profileBillingAddressData);

                //Set billing address
                $quote->getBillingAddress()->addData($profileBillingAddressData);
                $quote->getBillingAddress()->setCustomerId(
                    $profile->getCustomerId()
                );

                if ($isReBill && method_exists($this->getEngine(), 'setRebillProcessFlag')) {
                    $this->getEngine()->setRebillProcessFlag();
                }

                if (!$this->tempQuote) {
                    //Set payment method
                    $quote->getPayment()
                        ->importData($this->getEngine()->getPaymentInfo($profile))
                        ->setAdditionalInformation($this->getEngine()->getPaymentAdditionalInfo($profile));

                    if ($isReBill && method_exists($this->getEngine(), 'setPaymentExtensionAttributes')) {
                        $this->getEngine()->setPaymentExtensionAttributes($quote->getPayment(), $profile);
                    }
                }
                if (!$quote->isVirtual()) {
                    $profileShippingAddressData = $profile->getShippingAddress()->getData();
                    unset($profileShippingAddressData['id']);
                    $profileShippingAddressData = array_filter($profileShippingAddressData);

                    //Set shipping address
                    $quote->getShippingAddress()->addData($profileShippingAddressData);
                    $quote->getShippingAddress()->setCustomerId(
                        $profile->getCustomerId()
                    );

                    //Set shipping method
                    $quote->getShippingAddress()
                        ->setCollectShippingRates(true)
                        ->setItemQty($quote->getItemsSummaryQty());
                    $this->processShippingMethodRate($quote, $profile);
                }

                if (!$this->tempQuote) {
                    $this->quoteRepository->save($quote);
                }

                foreach ($quote->getAllAddresses() as $address) {
                    $address->unsetData('cached_items_all');
                }
                foreach ($quote->getAllItems() as $item) {
                    foreach ($quote->getAllAddresses() as $address) {
                        $address->addItem($item, $item->getQty());
                    }
                }

                $quote->setTotalsCollectedFlag(false);
                $quote->collectTotals();
            }
        }
        return $outOfStockProducts;
    }

    /**
     * @param Quote $quote
     * @param $profile
     * @return $this
     */
    protected function processShippingMethodRate($quote, $profile)
    {
        $shippingMethodToSet = $profile->getShippingMethod();
        $quote->getShippingAddress()->setShippingMethod($shippingMethodToSet);
        $this->totalsCollector->collectAddressTotals($quote, $quote->getShippingAddress());
        $quote->getShippingAddress()->collectShippingRates();
        $shippingMethodAvailable = false;
        $shippingRates = $quote->getShippingAddress()->getAllShippingRates();
        $ratesApplicable = [];
        $cheapestRate = '';
        if ($shippingRates) {
            $cheapestPrice = null;
            foreach ($shippingRates as $rate) {
                if ($rate->getCode() == $shippingMethodToSet) {
                    $shippingMethodAvailable = true;
                }
                $ratesApplicable[$rate->getCode()] = $rate->getPrice();
                if ($cheapestPrice === null || $cheapestPrice > $rate->getPrice()) {
                    $cheapestRate = $rate->getCode();
                    $cheapestPrice = $rate->getPrice();
                }
            }
        }
        if (
            !$shippingMethodAvailable
            && $this->mpowerConfig->getFreeShippingStrategy() == FreeShipping::HONOR_MAGENTO_VALUE
            && $shippingMethodToSet == 'freeshipping_freeshipping'
        ) {
            $quote->getShippingAddress()->addShippingRate($this->freeShipping->getCarrierRate($quote));
        } elseif (!$shippingMethodAvailable) {
            $shippingMethodToSet = $cheapestRate;
            if ($this->mpowerConfig->getShippingFallbackStrategy() == ShippingFallback::DEFAULT_VALUE) {
                $defaultShippingMethod = $this->mpowerConfig->getDefaultShippingMethod();
                if ($defaultShippingMethod && array_key_exists($defaultShippingMethod, $ratesApplicable)) {
                    $shippingMethodToSet = $defaultShippingMethod;
                }
            }
        }
        $quote->getShippingAddress()->setShippingMethod($shippingMethodToSet);
        return $this;
    }

    /**
     * @param ProductSubscriptionProfileInterface $profileProduct
     * @param bool $isRebill
     * @return DataObject
     */
    protected function getProductAddRequest(ProductSubscriptionProfileInterface $profileProduct, $isRebill = false)
    {
        $data = [
            'custom_price' => $profileProduct->getUnitPrice(),
            'qty' => $profileProduct->getQty(),
            Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                Create::UNIQUE => [
                    'use_preset_qty' => $profileProduct->getTnwSubscrUnlockPresetQty(),
                ],
                Create::NON_UNIQUE => [
                    'current_preset_qty_price' => $profileProduct->getPrice(),
                ],
                Create::FULL_REQUEST_PARAM_NAME => false,
            ]
        ];

        $customOptions = $profileProduct->getCustomOptions();
        if (isset($customOptions['info_buyRequest'])) {
            $data = array_replace_recursive($customOptions['info_buyRequest'], $data);
        }
        if ($this->profile) {
            $data['billing_frequency'] = $this->profile->getBillingFrequencyId();
            $data['term'] = $this->profile->getTerm();
            $data['period'] = $this->profile->getTotalBillingCycles();
        }

        if (
            $isRebill
            && $this->profile->getOrigData('billing_frequency_id') == $this->profile->getData('billing_frequency_id')
        ) {
            $data['rebill_processing'] = $isRebill;
        }
        return $this->dataObjectFactory->create($data);
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
            if ($value != null) {
                $this->getProfile()->getPayment()->$method($value);
            }
        }

        return $this;
    }

    /**
     * @param $vaultData
     * @return $this
     */
    public function populateCustomPaymentData($vaultData)
    {
        /** @var \Magento\Vault\Model\PaymentToken $vaultToken */
        $vaultToken = $vaultData['vault_payment_token'];
        $details = json_decode($vaultToken->getTokenDetails(), true);
        $expiresAt = strtotime($vaultToken->getExpiresAt());
        $expirationMonth = date('m', $expiresAt);
        $expirationYear = date('Y', $expiresAt);
        $data = [
           'encoded_payment_additional_info' => [
               'cc_type' => isset($details['type']) ? $details['type'] : $details['cc_type'],
               'cc_last_4' => isset($details['maskedCC']) ? $details['maskedCC'] : $details['cc_last_4'],
               'cc_exp_month' => $expirationMonth,
               'cc_exp_year' => $expirationYear
           ],
            'token_hash' => null
        ];
        $this->getProfile()->getPayment()->setEngineCode($vaultToken->getPaymentMethodCode());
        foreach ($data as $key => $value) {
            $method = 'set' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($key);
            $this->getProfile()->getPayment()->$method($value);
        }
        $this->getProfile()->getPayment()->setPaymentToken($vaultToken->getGatewayToken());
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
        $nextProfileRelation = $this->getNextProfileRelation();
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
        $profileBilling = $this->profileAddressFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $profileBilling,
            $quote->getBillingAddress()->toArray(),
            SubscriptionProfileAddressInterface::class
        );

        $profileShipping = $this->profileAddressFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $profileShipping,
            $quote->getShippingAddress()->toArray(),
            SubscriptionProfileAddressInterface::class
        );
        $profileBilling->unsetData('id');
        $profileShipping->unsetData('id');
        return [
            $profileBilling,
            $profileShipping
        ];
    }

    /**
     * @param array $quoteItems
     * @return array|bool|float|int|string|null
     */
    private function getUniqueBuyRequest(array $quoteItems)
    {
        /** @var Item $item */
        $item = reset($quoteItems);
        if (!$item instanceof \Magento\Quote\Model\Quote\Item) {
            return null;
        }

        $result = $this->serializer->unserialize($item->getOptionByCode('subscription')->getValue());

        return is_array($result) ? $result : [];
    }

    /**
     * Calculates start date of subscription when trial period is set.
     *
     * @return null|string
     * @throws LocalizedException
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
                    throw new LocalizedException(__('Undefined trial length unit type.'));
            }

            $expression = 'P' . $this->getProfile()->getTrialLength() . $intervalUnit;
            $result = $startDate->add(new \DateInterval($expression))
                ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
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
        //Add hours, minutes, and seconds to start date
        $expression = 'PT' . $date->format('H') . 'H'
            . $date->format('i') . 'M'
            . $date->format('s') . 'S';
        $startDate->add(new \DateInterval($expression));

        return $startDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
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
        $paymentPostData = $requestData['payment'] ?? [];
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
        return $requestData['shipping_method_id'] ?? null;
    }

    /**
     * @param OrderInterface $order
     * @param Quote $quote
     * @param $quoteItems
     * @param null $trialData
     * @return SubscriptionProfileInterface
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createByOrder(OrderInterface $order, Quote $quote, $quoteItems, $trialData = null)
    {
        /** @var OrderPaymentInterface $orderPayment */
        $orderPayment = $order->getPayment();
        $quotePayment = $quote->getPayment();
        $orderPaymentDataToAdd = [
            'cc_type' => $orderPayment->getCcType(),
            'cc_last_4' => $orderPayment->getCcLast4(),
            'cc_exp_month' => $orderPayment->getCcExpMonth(),
            'cc_exp_year' => $orderPayment->getCcExpYear(),
        ];
        $extensionAttributes = $orderPayment->getExtensionAttributes();
        if (
            $orderPaymentDataToAdd['cc_type'] == null
            && $extensionAttributes
        ) {
            $vaultPaymentToken = $extensionAttributes->getVaultPaymentToken();
            if ($vaultPaymentToken) {
                $details = $vaultPaymentToken->getDetails();
                if ($details) {
                    try {
                        $details = json_decode($details);
                    } catch (\Exception $e) {
                        $details = [];
                    }
                    if ($details) {
                        $orderPaymentDataToAdd = $details;
                    }
                }
            }
        } elseif ($extensionAttributes->getVaultPaymentToken()) {
            $quotePayment->setAdditionalInformation(
                'extension_attributes',
                $extensionAttributes->getVaultPaymentToken()->getTokenDetails()
            );
        }
        foreach ($orderPaymentDataToAdd as $key => $data) {
            if ($data) {
                $quotePayment->setData($key, $data);
            }
        }

        //TODO: Необходимо использовать Vault Payment
        $this
            ->reset()
            ->populateProfileData($quote, $quoteItems);
        $notCCMethod = false;
        if (isset($trialData['method']) && $trialData['method'] == 'checkmo') {
            $notCCMethod = true;
            $quotePayment->setMethod($trialData['method']);
            $this->getProfile()->getPayment()->setEngineCode($trialData['method']);
        }
        if ($trialData && !$notCCMethod) {
            $this->populateCustomPaymentData($trialData);
            $profile = $this->getProfile();
        } else {
            $this->populatePaymentData($quotePayment);
            $profile = $this->getProfile();
            if (
                $extensionAttributes instanceof OrderPaymentExtensionInterface &&
                ($paymentToken = $extensionAttributes->getVaultPaymentToken()) instanceof PaymentTokenInterface
            ) {
                /** @var $paymentToken PaymentTokenInterface */
                $profile->getPayment()->setPaymentToken($paymentToken->getGatewayToken());
            }
        }

        $oldStatus = $profile->getStatus();

        $status = ProfileStatus::STATUS_ACTIVE;
        if ($profile->getTrialStartDate()) {
            $startDate = $profile->getStartDate();
            if (\date_create()->diff(\date_create($startDate))->invert === 0) {
                $status = ProfileStatus::STATUS_TRIAL;
            }
        }

        $profile->setStatus($status);

        // Save profile
        $this->saveProfile();

        // Add comment about profile creation.
        $this->historyLogger->message(
            MessageHistoryLogger::MESSAGE_SUBSCRIPTION_CREATED,
            [
                $profile->getLabel()
            ],
            $profile->getId()
        );

        if ($oldStatus != $status) {
            //Add comment profile place.
            $this->historyLogger->message(
                MessageHistoryLogger::MESSAGE_SUBSCRIPTION_STATUS_CHANGED,
                [
                    $this->profileStatus->getLabelByValue($oldStatus),
                    $this->profileStatus->getLabelByValue($status)
                ],
                $profile->getId()
            );
        }

        // Add comment profile place.
        $this->historyLogger->message(
            MessageHistoryLogger::MESSAGE_ORDER_CREATED_FROM_QUOTE,
            [
                $order->getEntityId(),
                $order->getIncrementId(),
                $this->historyLogger->getConvertedQuoteId($quote->getId())
            ],
            $profile->getId()
        );

        $startDate = $profile->getTrialStartDate() ?: $profile->getStartDate();
        //Assign quote to new profile
        $relation = $this->assignQuoteToProfile($quote, $profile, $startDate);
        //Add new relation to profile processing queue in "pending" state.
        $queueItemIds = $this->resourceQueue->insertItems($relation->getId());
        $this->resourceQueue->updateStatus($queueItemIds, QueueStatus::QUEUE_STATUS_RUNNING);

        $this->assignOrderToProfile($relation, $order);
        $this->resourceQueue->updateStatus($queueItemIds, QueueStatus::QUEUE_STATUS_COMPLETE);

        return $profile;
    }

    /**
     * Check products type in subscription
     *
     * @param $quoteItems
     * @return bool
     */
    public function isQuoteHasVirtualProducts(array $quoteItems)
    {
        foreach ($quoteItems as $item) {
            if (!$item->getIsVirtual() && $item->getProductType() != 'virtual') {
                return false;
            } else {
                continue;
            }
        }
        return true;
    }
}
