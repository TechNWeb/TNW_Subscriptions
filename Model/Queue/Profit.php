<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Queue;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationListInterface;
use Magento\Bundle\Model\Product\Type;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileProfit;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\ProfitCalculator;

/**
 * Class Profit - calculates profit and set it in table
 */
class Profit
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProductBillingFrequencyRepositoryInterface
     */
    private $recurringOptionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SubscriptionProfileProfit
     */
    private $subscriptionProfileProfit;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var Serializer
     */
    private $jsonHelper;

    /**
     * @var SubscriptionProfile
     */
    private $subscriptionProfileResource;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Profit constructor.
     * @param LoggerInterface $logger
     * @param Serializer $jsonHelper
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param SubscriptionProfileProfit $subscriptionProfileProfit
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductBillingFrequencyRepositoryInterface $recurringOptionRepository
     * @param SubscriptionProfile $subscriptionProfileResource
     * @param EntityManager $entityManager
     */
    public function __construct(
        LoggerInterface $logger,
        Serializer $jsonHelper,
        SubscriptionProfileRepositoryInterface $profileRepository,
        SubscriptionProfileProfit $subscriptionProfileProfit,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductBillingFrequencyRepositoryInterface $recurringOptionRepository,
        SubscriptionProfile $subscriptionProfileResource,
        EntityManager $entityManager
    ) {
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->profileRepository = $profileRepository;
        $this->subscriptionProfileProfit = $subscriptionProfileProfit;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->recurringOptionRepository = $recurringOptionRepository;
        $this->subscriptionProfileResource = $subscriptionProfileResource;
        $this->entityManager = $entityManager;
    }

    /**
     * @param OperationInterface $operation
     * @return void
     * @throws \Exception
     */
    public function processOperation(OperationInterface $operation)
    {
        try {
            $serializedData = $operation->getSerializedData();
            $unserializedData = $this->jsonHelper->unserialize($serializedData);
            foreach ($unserializedData as $unserialized) {
                foreach ($unserialized as $key => $value) {
                    $this->profileRepository->unsetLoadedProfileData($value);
                    $profile = $this->profileRepository->getById($value);
                    $this->calculateProfitAndSave($profile);
                }
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $status = ($e instanceof TemporaryStateExceptionInterface)
                ? OperationInterface::STATUS_TYPE_RETRIABLY_FAILED
                : OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during product attributes update. Please see log for details.');
        }

        $operation->setStatus($status ?? OperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }

    /**
     * @param OperationListInterface $operationList
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function processOperations(OperationListInterface $operationList)
    {
        foreach ($operationList->getItems() as $operation) {
            $this->processOperation($operation);
        }
    }

    /**
     * @param $profile
     * @return float|int|mixed|null
     * @throws LocalizedException
     */
    public function calculateProfitAndSave($profile)
    {
        $profit = 0;
        $lifetimeValue = 0;
        if ($profile->getStatus() == ProfileStatus::STATUS_CANCELED
        ) {
            return $profit;
        }
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
                array_map([$this, 'profileItemProductId'], $profile->getProducts()),
                'in'
            )
            ->addFilter(ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID, $profile->getBillingFrequencyId())
            ->create();

        $recurringOptions = $this->recurringOptionRepository
            ->getList($searchCriteria)
            ->getItems();

        /** @var ProductSubscriptionProfileInterface $profileProduct */
        foreach ($profile->getVisibleProducts() as $profileProduct) {
            $children = $profileProduct->getChildren();

            if (!empty($children)
                && $this->searchRecurringOption($recurringOptions, \reset($children)->getMagentoProductId())
                && $profileProduct->getMagentoProduct()->getTypeId() !== Type::TYPE_CODE
            ) {
                $profileProduct = \reset($children);
            }

            $recurringOption = $this->searchRecurringOption($recurringOptions, $profileProduct->getMagentoProductId());

            if (empty($recurringOption)) {
                continue;
            }

            $invoiceItems = $this->subscriptionProfileResource->getInvoiceItems($profile);
            $initialFeeAdded = false;
            foreach ($invoiceItems as $item) {
                if (!$initialFeeAdded
                    && isset($recurringOption['initial_fee'])
                    && (float)$recurringOption['initial_fee']
                ) {
                    $initialFeeAdded = true;
                    $profit += $recurringOption['initial_fee'];
                    $lifetimeValue += $recurringOption['initial_fee'];
                }
                $profit += ($item['base_price_incl_tax'] - $item['base_cost']) * $item['qty'];
                $lifetimeValue += $item['base_price_incl_tax'] * $item['qty'];

                // Bundle products store cost in their children
                if ($item['product_type'] === Type::TYPE_CODE) {
                    $bundleChildrenInvoiceItems = $this->subscriptionProfileResource->getInvoiceItemChildren(
                        $item['order_item_id'],
                    );
                    $lastChildrenCost = 0;
                    foreach ($bundleChildrenInvoiceItems as $bundleChildrenInvoiceItem) {
                        $cost = $bundleChildrenInvoiceItem['base_cost'] * $bundleChildrenInvoiceItem['qty'];
                        $profit -= $cost;
                        $lastChildrenCost += $cost;
                    }
                }
            }
        }

        $this->setProfitData($profile, $profit, ProfitCalculator::AS_OF_TODAY);
        $this->setProfitData($profile, $lifetimeValue, ProfitCalculator::LIFETIME);

        if (isset($item)) {
            $profitOfLastItem = ($item['base_price_incl_tax'] - $item['base_cost']) * $item['qty'];
            if (isset($lastChildrenCost)) {
                $profitOfLastItem -= $lastChildrenCost;
            }
        } else {
            $profitOfLastItem = 0;
        }

        $asTodayProfit = $profit;

        if ($profile->getTerm() == 1) {
            if ($profile->getUnit() == 3) {
                $profit = ($profitOfLastItem * 365 / $profile->getFrequency()) - $asTodayProfit;
            } else {
                $profit = ($profitOfLastItem * 12 / $profile->getFrequency()) - $asTodayProfit;
            }
        } else {
            $profit += ($profitOfLastItem * $profile->getTotalBillingCycles()) - $asTodayProfit;
        }

        $this->setProfitData($profile, $profit, ProfitCalculator::REMAINING);

        return $profit;
    }

    /**
     * @param $recurringOptions
     * @param $productId
     * @return false|mixed
     */
    private function searchRecurringOption($recurringOptions, $productId)
    {
        $filteredRecurringOptions = array_filter(
            $recurringOptions,
            function (ProductBillingFrequencyInterface $frequency) use ($productId) {
                return (int) $frequency->getMagentoProductId() === (int) $productId;
            }
        );

        return \reset($filteredRecurringOptions);
    }

    /**
     * @param ProductSubscriptionProfileInterface $item
     *
     * @return null|string
     */
    private function profileItemProductId(ProductSubscriptionProfileInterface $item)
    {
        return $item->getMagentoProductId();
    }

    /**
     * Save or update profit data
     *
     * @param $profile
     * @param $profit
     * @param $profitType
     */
    private function setProfitData($profile, $profit, $profitType)
    {
        try {
            $data = [
                'profile_id' => $profile->getId(),
                'profit_type' => $profitType,
                'total_profit' => $profit
            ];

            if ($this->subscriptionProfileProfit->getTotalProfitById(
                $profile->getId(),
                $profitType
            ) == null
            ) {
                $this->subscriptionProfileProfit->setTotalProfit($data);
            } else {
                $this->subscriptionProfileProfit->updateTotalProfit($data);
            }
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
