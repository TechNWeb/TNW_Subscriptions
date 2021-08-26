<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ProductBillingFrequency\Queue;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Bulk\OperationInterface as BulkOperationInterface;
use Magento\Framework\DB\Adapter\ConnectionException;
use Magento\Framework\DB\Adapter\DeadlockException;
use Magento\Framework\DB\Adapter\LockWaitException;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\Backend\Product\Attribute\DiscountAmount;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency;
use TNW\Subscriptions\Model\ProductBillingFrequencyFactory;
use TNW\Subscriptions\Model\ProductBillingFrequencyRepository;

/**
 * Consumer for linking products to billing frequency in bulk
 */
class Consumer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var BillingFrequencyRepositoryInterface
     */
    private $billingFrequencyRepository;

    /**
     * @var ProductBillingFrequencyRepository
     */
    private $productBillingFrequencyRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductBillingFrequencyFactory
     */
    private $productBillingFrequencyFactory;

    /**
     * @var array
     */
    private $defaultFrequencyForIds;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var array
     */
    private $defaultPrices;

    /**
     * Consumer constructor.
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param EntityManager $entityManager
     * @param BillingFrequencyRepositoryInterface $billingFrequencyRepository
     * @param ProductBillingFrequencyRepository $productBillingFrequencyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductBillingFrequencyFactory $productBillingFrequencyFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        EntityManager $entityManager,
        BillingFrequencyRepositoryInterface $billingFrequencyRepository,
        ProductBillingFrequencyRepository $productBillingFrequencyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductBillingFrequencyFactory $productBillingFrequencyFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productBillingFrequencyFactory = $productBillingFrequencyFactory;
        $this->productRepository = $productRepository;
    }

    /**
     * Process link product operation
     * @param OperationInterface $operation
     * @return void
     * @throws \Exception
     */
    public function process(OperationInterface $operation)
    {
        try {
            $serializedData = $operation->getSerializedData();
            $data = $this->serializer->unserialize($serializedData);
            $this->execute($data);
        } catch (\Zend_Db_Adapter_Exception $e) {
            $this->logger->critical($e->getMessage());
            if ($e instanceof LockWaitException
                || $e instanceof DeadlockException
                || $e instanceof ConnectionException
            ) {
                $status = BulkOperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = $e->getMessage();
            } else {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __(
                    'Sorry, something went wrong during product attributes update. Please see log for details.'
                );
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
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function execute($data)
    {
        $this->billingFrequencyRepository->getById($data['frequency_id']);
        $this->defaultFrequencyForIds = $this->getIsDefaultFrequencyForProductIds($data);
        $this->defaultPrices = $this->getDefaultPrices($data);
        foreach ($data['product_ids'] as $product_id) {
            $linkCandidate = $this->prepareLinkedProduct($product_id, $data['frequency_id']);
            $this->productBillingFrequencyRepository->save($linkCandidate);
        }
    }

    /**
     * Get product Ids, which are not linked yet to any frequency
     * @param $data
     * @return array
     * @throws LocalizedException
     */
    private function getIsDefaultFrequencyForProductIds($data)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
            $data['product_ids'],
            'in'
        )->create();
        $existing = [];
        foreach ($this->productBillingFrequencyRepository->getList($searchCriteria)->getItems() as $item) {
            $existing[] = $item->getMagentoProductId();
        }
        return array_diff($data['product_ids'], $existing);
    }

    /**
     * Prepare new linked product object
     * @param $productId
     * @param $frequencyId
     * @return ProductBillingFrequency
     */
    private function prepareLinkedProduct($productId, $frequencyId)
    {
        $linkedProduct = $this->productBillingFrequencyFactory->create();
        $linkedProduct->setBillingFrequencyId($frequencyId)
            ->setMagentoProductId($productId)
            ->setPrice($this->defaultPrices[$productId])
            ->setInitialFee(0)
            ->setPresetQty(1)
            ->setDefaultBillingFrequency(in_array($productId, $this->defaultFrequencyForIds) ? 1 : 0);
        return $linkedProduct;
    }

    /**
     * Get default product prices array indexed by product id
     * @param $data array
     * @return array
     */
    private function getDefaultPrices($data)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'entity_id',
            $data['product_ids'],
            'in'
        )->create();
        $defaultPrices = [];
        foreach ($this->productRepository->getList($searchCriteria)->getItems() as $product) {
            $defaultPrices[$product->getId()] = $this->getDefaultPrice($product);
        }
        return $defaultPrices;
    }

    /**
     * Get calculated subscription price
     * @param $product ProductInterface
     * @return float|int
     */
    private function getDefaultPrice($product)
    {
        $price = $product->getPrice();
        $isPriceLocked = $product->hasData(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE)
            && $product->getData(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE);
        $isDiscount = $product->hasData(Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT)
            && $product->getData(Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT);
        $discountType = $product->hasData(Attribute::SUBSCRIPTION_DISCOUNT_TYPE)
            ? $product->getData(Attribute::SUBSCRIPTION_DISCOUNT_TYPE)
            : 0;
        $discountAmount = $product->hasData(Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT)
            ? $product->getData(Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT)
            : 0;
        if ($isPriceLocked && $isDiscount && $discountType && $discountAmount) {
            if ($discountType == DiscountAmount::PERCENT_DISCOUNT) {
                $price *= (1 - ($discountAmount / 100));
            }
            if ($discountType == DiscountAmount::FLAT_FEE_DISCOUNT) {
                $price -= (float)$discountAmount;
            }
        }
        return $price;
    }
}
