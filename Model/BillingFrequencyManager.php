<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency\CollectionFactory
    as ProductBillingFrequencyCollectionFactory;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\Product\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\ActionFactory;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;

/**
 * Class BillingFrequencyManager - used as manager for billing frequency
 */
class BillingFrequencyManager
{
    /**
     * @var BillingFrequencyRepositoryInterface
     */
    private $billingFrequencyRepository;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductBillingFrequencyRepositoryInterface
     */
    private $productBillingFrequencyRepository;

    /**
     * @var ProductBillingFrequencyCollectionFactory
     */
    private $productBillingFrequencyCollectionFactory;

    /**
     * @var ActionFactory
     */
    private $actionFactory;

    /**
     * BillingFrequencyManager constructor.
     * @param BillingFrequencyRepositoryInterface $billingFrequencyRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository
     * @param ProductBillingFrequencyCollectionFactory $productBillingFrequencyCollectionFactory
     * @param ActionFactory $actionFactory
     */
    public function __construct(
        BillingFrequencyRepositoryInterface $billingFrequencyRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository,
        ProductBillingFrequencyCollectionFactory $productBillingFrequencyCollectionFactory,
        ActionFactory $actionFactory
    ) {
        $this->productBillingFrequencyCollectionFactory = $productBillingFrequencyCollectionFactory;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->actionFactory = $actionFactory;
    }

    /**
     * @param $billingFrequency
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveBillingFrequency($billingFrequency)
    {
        if ($billingFrequency->getId()) {
            $storedData = $billingFrequency->getStoredData();
            if ($billingFrequency->getStatus() != $storedData[$billingFrequency::STATUS]) {
                $this->processStatusUpdate(
                    $billingFrequency->getId(),
                    $billingFrequency->getStatus(),
                    $billingFrequency->getWebsiteId()
                );
            }
        }
        $this->billingFrequencyRepository->save($billingFrequency);
    }

    /**
     * @param $billingFrequencyId
     * @param $status
     * @param $websiteId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processStatusUpdate($billingFrequencyId, $status, $websiteId)
    {
        if ($status == 0) {
            $productIds = $this->productBillingFrequencyCollectionFactory
                ->create()
                ->addFieldToSelect(ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID)
                ->addFieldToFilter(
                    ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID,
                    ['eq' => $billingFrequencyId]
                )
                ->load()
                ->toArray();
            if (isset($productIds['items']) && $productIds['items']) {
                $productIds = array_column($productIds['items'], 'magento_product_id');
            } else {
                $productIds = [];
            }
            $filterByProductId = $this->filterBuilder
                ->setField(
                    ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID
                )
                ->setConditionType('in')
                ->setValue($productIds)
                ->create();
            $searchCriteria = $this->searchCriteriaBuilder->addFilters([$filterByProductId])
                ->create();
            $productBillingFrequencyItems = $this->productBillingFrequencyRepository->getList($searchCriteria)
                ->getItems();
            $affectedProductIds = [];
            $productIdsDefaultBillingFrequencyChanged = [];
            foreach ($productBillingFrequencyItems as $productBillingFrequencyItem) {
                if ($productBillingFrequencyItem->getBillingFrequencyId() == $billingFrequencyId) {
                    $productBillingFrequencyItem->setIsDisabled(true);
                    if ($productBillingFrequencyItem->getDefaultBillingFrequency()) {
                        $productBillingFrequencyItem->setDefaultBillingFrequency(0);
                        $productIdsDefaultBillingFrequencyChanged[] = $productBillingFrequencyItem
                            ->getMagentoProductId();
                    }
                }
                $affectedProductIds[$productBillingFrequencyItem->getMagentoProductId()][]
                    = $productBillingFrequencyItem;
            }
            $productsToDisableSubscriptionPossibility = [];
            foreach ($affectedProductIds as $productId => $productBillingFrequencies) {
                $isDefaultSelected = false;
                if (count($productBillingFrequencies) > 1) {
                    foreach ($productBillingFrequencies as $billingFrequencyProduct) {
                        if (!$billingFrequencyProduct->getIsDisabled()
                            && in_array(
                                $billingFrequencyProduct->getMagentoProductId(),
                                $productIdsDefaultBillingFrequencyChanged
                            )
                        ) {
                            $billingFrequencyProduct->setDefaultBillingFrequency(1);
                            $isDefaultSelected = true;
                        } elseif (!$billingFrequencyProduct->getIsDisabled()) {
                            $isDefaultSelected = true;
                        }
                    }
                }
                if (!$isDefaultSelected) {
                    $productsToDisableSubscriptionPossibility[] = $productId;
                }
            }
            if ($productsToDisableSubscriptionPossibility) {
                $this->actionFactory->create()->updateAttributes(
                    $productsToDisableSubscriptionPossibility,
                    [Attribute::SUBSCRIPTION_PURCHASE_TYPE => PurchaseType::ONE_TIME_PURCHASE_TYPE],
                    $websiteId
                );
            }
        } else {
            $productBillingFrequencyItems = $this->productBillingFrequencyRepository
                ->getListByFrequencyId($billingFrequencyId)
                ->getItems();
            foreach ($productBillingFrequencyItems as $productBillingFrequencyItem) {
                $productBillingFrequencyItem->setIsDisabled(0);
            }
        }
        foreach ($productBillingFrequencyItems as $billingFrequencyItem) {
            $this->productBillingFrequencyRepository->save($billingFrequencyItem);
        }
    }
}
