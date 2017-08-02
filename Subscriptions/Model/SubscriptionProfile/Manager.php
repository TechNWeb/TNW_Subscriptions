<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\EngineInterface;
use TNW\Subscriptions\Model\SubscriptionProfileFactory;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\Manager as ProductManager;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use Magento\Sales\Api\Data\OrderInterface;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;

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
     * Profile to order relation manager.
     *
     * @var OrderRelationManager
     */
    private $orderRelationManager;

    /**
     * Manager constructor.
     * @param EnginePool $enginePool
     * @param SubscriptionProfileRepository $subscriptionProfileRepository
     * @param SubscriptionProfileFactory $subscriptionProfileFactory
     * @param BillingFrequencyRepositoryInterface $frequencyRepository
     * @param DataObjectHelper $dataObjectHelper
     * @param AddressFactory $profileAddressFactory
     * @param ProductManager $productManager
     * @param OrderRelationManager $orderRelationManager
     */
    public function __construct(
        EnginePool $enginePool,
        SubscriptionProfileRepository $subscriptionProfileRepository,
        SubscriptionProfileFactory $subscriptionProfileFactory,
        BillingFrequencyRepositoryInterface $frequencyRepository,
        DataObjectHelper $dataObjectHelper,
        AddressFactory $profileAddressFactory,
        ProductManager $productManager,
        OrderRelationManager $orderRelationManager
    ) {
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->subscriptionProfileFactory = $subscriptionProfileFactory;
        $this->enginePool = $enginePool;
        $this->frequencyRepository = $frequencyRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->profileAddressFactory = $profileAddressFactory;
        $this->productManager = $productManager;
        $this->orderRelationManager = $orderRelationManager;
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
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;
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
        $this->getEngine()->updateProfile(
            $this->getProfile()
        );
        $this->setProfile($this->subscriptionProfileRepository->save($this->getProfile()));

        return $this->getProfile();
    }

    /**
     * Engine profile processing.
     *
     * Processes subscription profile.
     */
    public function processProfile()
    {
        $this->getEngine()->processProfile(
            $this->getProfile()
        );
    }

    /**
     * Assigns order to profile.
     *
     * @param OrderInterface $order
     * @param null $profile
     */
    public function assignOrderToProfile(
        OrderInterface $order,
        $profile = null
    ) {
        if (!$profile) {
            $profile = $this->getProfile();
        }

        $relation = $this->orderRelationManager
            ->getNewProfileOrderReletion()
            ->setSubscriptionProfileId($profile->getId())
            ->setMagentoOrderId($order->getId());
        $this->orderRelationManager->saveRelation($relation);
    }

    /**
     * Set data to profile from quote.
     *
     * @param Quote $quote
     * @return $this
     * @throws \Exception
     */
    public function populateProfileData($quote)
    {
        $request = $this->getUniqueBuyRequest($quote);

        if (!empty($request)) {
            $frequency = $this->frequencyRepository->getById($request['billing_frequency']);


            if (!$frequency || !$frequency->getId()) {
                throw new \Exception(__('Can not create profile with empty frequency.'));
            }
        }

        if (isset($frequency)){
            $this->getProfile()
                ->setCustomerId($quote->getCustomerId())
                ->setWebsiteId($quote->getStore()->getWebsiteId())
                ->setEngineCode($quote->getPayment()->getMethod())
                ->setShippingMethod($quote->getShippingAddress()->getShippingMethod())
                ->setShippingDescription($quote->getShippingAddress()->getShippingDescription())
                ->setIsVirtual($quote->getIsVirtual())
                ->setProfileCurrencyCode($quote->getQuoteCurrencyCode())
                ->setTerm($request['term'])
                ->setTotalBillingCycles($request['period'])
                ->setStartDate($request['start_on'])
                ->setBillingFrequencyId($frequency->getId())
                ->setFrequency($frequency->getFrequency())
                ->setUnit($frequency->getUnit())
                ->setStatus(ProfileStatus::STATUS_PENDING)
                ->setTrialStartDate(null)
                ->setTrialLength($request['trial_period'])
                ->setTrialLengthUnit($request['trial_unit_id']);

            if ($request['is_trial']){
                $this->getProfile()->setTrialStartDate($request['start_on']);
                $this->getProfile()->setStartDate($this->calculateStartDate());
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
     * Returns list of profile addresses created from billing and shipping addresses.
     *
     * @param Quote $quote
     * @return array
     */
    private function populateAddressesData($quote)
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

        if ($this->getProfile()->getTrialStartDate()){
            $startDate = new \DateTime($this->getProfile()->getTrialStartDate());

            switch ($this->getProfile()->getTrialLengthUnit()){
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

            $expretion = 'P' . $this->getProfile()->getTrialLength() . $intervalUnit;
            $result = $startDate->add(new \DateInterval($expretion))
                ->format('Y-m-d');
        }

        return $result;
    }
}