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
use Magento\Catalog\Model\ResourceModel\Product\ActionFactory;

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
     * @var ProductBillingFrequencyFactory
     */
    private $productBillingFrequencyFactory;

    /**
     * BillingFrequencyManager constructor.
     * @param BillingFrequencyRepositoryInterface $billingFrequencyRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository
     * @param ProductBillingFrequencyCollectionFactory $productBillingFrequencyCollectionFactory
     * @param ActionFactory $actionFactory
     * @param ProductBillingFrequencyFactory $productBillingFrequencyFactory
     */
    public function __construct(
        BillingFrequencyRepositoryInterface $billingFrequencyRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository,
        ProductBillingFrequencyCollectionFactory $productBillingFrequencyCollectionFactory,
        ActionFactory $actionFactory,
        ProductBillingFrequencyFactory $productBillingFrequencyFactory
    ) {
        $this->productBillingFrequencyCollectionFactory = $productBillingFrequencyCollectionFactory;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->actionFactory = $actionFactory;
        $this->productBillingFrequencyFactory = $productBillingFrequencyFactory;
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
        $this->saveLinkedProducts($billingFrequency);
        if ($storedData && $billingFrequency->getStatus() != $storedData[$billingFrequency::STATUS]) {
            $this->processStatusUpdate($billingFrequency->getId(), $billingFrequency->getStatus());
        }
    }

    /**
     * @param BillingFrequency $billingFrequency
     * @return BillingFrequency
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveLinkedProducts(BillingFrequency $billingFrequency)
    {
        $currentLinkedProducts = $this->productBillingFrequencyRepository
            ->getListByFrequencyId($billingFrequency->getId())
            ->getItems();
        foreach ($currentLinkedProducts as $currentLinkedProduct) {
            $this->productBillingFrequencyRepository->delete($currentLinkedProduct);
        }

        if ($billingFrequency->getData('links') && $billingFrequency->getData('links')['linked']) {
            $linkedProductData = $billingFrequency->getData('links')['linked'];
            $isDefaultDataNewProducts = $this->getIsDefaultBillingFrequencyForNewProducts(
                $linkedProductData,
                $currentLinkedProducts
            );
            $maxOrder = 0;
            foreach ($linkedProductData as $data) {
                $data['default_billing_frequency'] = $this->isDefaultBillingFrequency(
                    $data,
                    $currentLinkedProducts,
                    $isDefaultDataNewProducts
                );
                $linkedProduct = $this->prepareLinkedProduct($billingFrequency, $data, $maxOrder++);
                $this->productBillingFrequencyRepository->save($linkedProduct);
            }
        }

        return $billingFrequency;
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

    /**
     * @param array $newLinkedProductData
     * @param array $currentLinkedProducts
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getIsDefaultBillingFrequencyForNewProducts(
        array $newLinkedProductData,
        array $currentLinkedProducts
    ) {
        $addedProductsIds = [];
        $productsWithoutFrequencies = [];
        //Search for added to billing frequency products.
        foreach ($newLinkedProductData as $newProductData) {
            $existedProduct = false;
            foreach ($currentLinkedProducts as $currentLinkedProduct) {
                if ($currentLinkedProduct->getMagentoProductId() == $newProductData['id']) {
                    $existedProduct = true;
                    break;
                }
            }
            if (!$existedProduct) {
                $addedProductsIds[] = $newProductData['id'];
            }
        }
        //Search for products without billing frequencies.
        if (!empty($addedProductsIds)) {
            $this->searchCriteriaBuilder->addFilter(
                ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
                $addedProductsIds,
                'in'
            );
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $foundBillingFrequencies = $this->productBillingFrequencyRepository->getList($searchCriteria);
            foreach ($addedProductsIds as $addedProductId) {
                $productHasFrequency = false;
                foreach ($foundBillingFrequencies->getItems() as $productBillingFrequency) {
                    if ($productBillingFrequency->getMagentoProductId() == $addedProductId) {
                        $productHasFrequency = true;
                        break;
                    }
                }
                if (!$productHasFrequency) {
                    $productsWithoutFrequencies[$addedProductId] = 1;
                }
            }
        }

        return $productsWithoutFrequencies;
    }

    /**
     * Set default_billing_frequency value for product.
     *
     * @param array $data
     * @param array $earlierLinkedProducts
     * @param array $isDefaultDataNewProducts
     * @return int
     */
    private function isDefaultBillingFrequency(
        array $data,
        array $earlierLinkedProducts,
        array $isDefaultDataNewProducts
    ) {
        $isDefault = 0;
        $productId = $data['id'];
        foreach ($earlierLinkedProducts as $linkedProduct) {
            if ($productId == $linkedProduct->getMagentoProductId()) {
                $isDefault = $linkedProduct->getDefaultBillingFrequency();
                break;
            }
        }
        if (isset($isDefaultDataNewProducts[$productId]) && ($isDefaultDataNewProducts[$productId] == 1)) {
            $isDefault = 1;
        }
        return $isDefault;
    }

    /**
     * Prepares linked product.
     *
     * @param BillingFrequency $result
     * @param array $data
     * @param int $maxOrder
     * @return ProductBillingFrequencyInterface
     */
    private function prepareLinkedProduct(BillingFrequency $result, array $data, $maxOrder)
    {
        $linkedProduct = $this->productBillingFrequencyFactory->create();
        $resultData = [
            ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID => $result->getId(),
            ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID => $this->prepareValue($data, 'id'),
            ProductBillingFrequencyInterface::DEFAULT_BILLING_FREQUENCY => $this->prepareValue(
                $data,
                'default_billing_frequency'
            ),
            ProductBillingFrequencyInterface::PRICE => $this->prepareValue($data, 'price'),
            ProductBillingFrequencyInterface::INITIAL_FEE => $this->prepareValue($data, 'initial_fee'),
            ProductBillingFrequencyInterface::PRESET_QTY => $this->prepareValue($data, 'preset_qty'),
            ProductBillingFrequencyInterface::IS_DISABLED => $this->prepareValue($data, 'is_disabled'),
            'sort_order' => $maxOrder
        ];
        $linkedProduct->setData($resultData);

        return $linkedProduct;
    }

    /**
     * Prepares value before saving.
     *
     * @param [] $data
     * @param string $field
     * @return string
     */
    private function prepareValue($data, $field)
    {
        return !empty($data[$field]) ? $data[$field] : '';
    }
}
