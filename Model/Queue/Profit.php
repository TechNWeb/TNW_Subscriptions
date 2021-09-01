<?php

namespace TNW\Subscriptions\Model\Queue;

use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use Magento\AsynchronousOperations\Api\Data\OperationListInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Serialize\Serializer\Json as Serializer;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileProfit;

class Profit
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        Serializer $jsonHelper,
        SubscriptionProfileRepositoryInterface $profileRepository,
        SubscriptionProfileProfit $subscriptionProfileProfit
    ) {
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->profileRepository = $profileRepository;
        $this->subscriptionProfileProfit = $subscriptionProfileProfit;
    }


    /**
     * @param OperationInterface $operation
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processOperation(OperationInterface $operation)
    {
        $serializedData = $operation->getResultSerializedData();
        $message = null;
        $unserializedData = $this->jsonHelper->unserialize($serializedData);
        foreach ($unserializedData as $key => $value) {
            $profile = $this->profileRepository->getById($value);
            $this->calculateProfitAndSave($profile);
        }
    }

    public function processOperations(OperationListInterface $operationList)
    {
        foreach ($operationList->getItems() as $operation) {
            $this->processOperation($operation);
        }
    }

    /**
     * @param $profile
     * @return float|int|mixed|null
     */
    public function calculateProfitAndSave($profile)
    {
        $profit = 0;

        $resource = $profile->getResource();
        $connection = $resource->getConnection();

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

            if (!empty($children) &&
                $this->searchRecurringOption($recurringOptions, \reset($children)->getMagentoProductId())
            ) {
                $profileProduct = \reset($children);
            }

            $product = $profileProduct->getMagentoProduct();
            $recurringOption = $this->searchRecurringOption($recurringOptions, $product->getId());

            if (empty($recurringOption)) {
                continue;
            }
            $select = $connection->select()
                ->from(
                    ['invoiceItem' => $resource->getTable('sales_invoice_item')]
                )
                ->joinInner(
                    ['salesRelative' => $resource->getTable('tnw_subscriptions_profile_item_sales_item')],
                    'invoiceItem.order_item_id = salesRelative.order_item_id',
                    []
                )
                ->joinInner(
                    ['profileItem' => $resource->getTable('tnw_subscriptions_product_subscription_profile_entity')],
                    'salesRelative.profile_item_id = profileItem.entity_id',
                    []
                )
                ->where('profileItem.subscription_profile_id = ?', $profile->getId());

            $invoiceItems = $connection->fetchAll($select);
            $initialFeeAdded = false;
            foreach ($invoiceItems as $item) {
                if (!$initialFeeAdded && isset($recurringOption['initial_fee']) && $recurringOption['initial_fee']) {
                    $initialFeeAdded = true;
                    $profit += $recurringOption['initial_fee'];
                }
                $profit += ($item['base_price'] - $item['base_cost']) * $item['qty'];
            }
        }

        switch ($profitType) {
            case self::AS_OF_TODAY:
                break;
            case self::REMAINING:
                $lastInvoiceItem = array_pop($invoiceItems);
                if ($lastInvoiceItem) {
                    $profitOfLastItem = ($lastInvoiceItem['base_price'] - $lastInvoiceItem['base_cost'])
                        * $lastInvoiceItem['qty'];
                } else {
                    $profitOfLastItem = 0;
                }

                if ($profile->getTerm() == 1) {
                    if ($profile->getUnit() == 3) {
                        $profit = $profitOfLastItem * 365 / $profile->getFrequency();
                        break;
                    } else {
                        $profit = $profitOfLastItem * 12 / $profile->getFrequency();
                        break;
                    }
                } else {
                    $profit += $profitOfLastItem * $profile->getTotalBillingCycles();
                    return $profit;
                }
            default:
                return null;
        }

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
                return (int)$frequency->getMagentoProductId() === (int)$productId;
            }
        );

        return \reset($filteredRecurringOptions);
    }
}
