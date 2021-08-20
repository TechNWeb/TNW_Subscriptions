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
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
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
     * BillingFrequencyManager constructor.
     * @param BillingFrequencyRepositoryInterface $billingFrequencyRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository
     * @param ProductBillingFrequencyCollectionFactory $productBillingFrequencyCollectionFactory
     */
    public function __construct(
        BillingFrequencyRepositoryInterface $billingFrequencyRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository,
        ProductBillingFrequencyCollectionFactory $productBillingFrequencyCollectionFactory
    ) {
        $this->productBillingFrequencyCollectionFactory = $productBillingFrequencyCollectionFactory;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param $billingFrequency
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveBillingFrequency($billingFrequency)
    {
        $storedData = [];
        if ($billingFrequency->getId()) {
            $storedData = $billingFrequency->getStoredData();
        }
        $this->billingFrequencyRepository->save($billingFrequency);
        if ($storedData && $billingFrequency->getStatus() != $storedData[$billingFrequency::STATUS]) {
            $this->processStatusUpdate($billingFrequency->getId(), $billingFrequency->getStatus());
        }
    }

    /**
     * @param $billingFrequencyId
     * @param $status
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processStatusUpdate($billingFrequencyId, $status)
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
            foreach ($affectedProductIds as $productId => $productBillingFrequencies) {
                if (count($productBillingFrequencies) > 1) {
                    foreach ($productBillingFrequencies as $billingFrequencyProduct) {
                        if (!$billingFrequencyProduct->getIsDisabled()
                            && in_array(
                                $billingFrequencyProduct->getMagentoProductId(),
                                $productIdsDefaultBillingFrequencyChanged
                            )
                        ) {
                            $billingFrequencyProduct->setDefaultBillingFrequency(1);
                        }
                    }
                }
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
