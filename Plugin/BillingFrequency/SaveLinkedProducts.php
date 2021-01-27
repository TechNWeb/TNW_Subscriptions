<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Plugin\BillingFrequency;

use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencySearchResultsInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Model\BillingFrequency;
use TNW\Subscriptions\Model\ProductBillingFrequencyFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Plugin to save links between billing frequency and products.
 */
class SaveLinkedProducts
{
    /**
     * Repository for saving/deleting/retrieving product billing frequencies.
     *
     * @var ProductBillingFrequencyRepositoryInterface
     */
    private $productBillingFrequencyRepository;

    /**
     * Factory for creating product billing frequencies.
     *
     * @var ProductBillingFrequencyFactory
     */
    private $productBillingFrequencyFactory;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * SaveLinkedProducts constructor.
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository
     * @param ProductBillingFrequencyFactory $productBillingFrequencyFactory
     */
    public function __construct(
        ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository,
        ProductBillingFrequencyFactory $productBillingFrequencyFactory,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->productBillingFrequencyFactory = $productBillingFrequencyFactory;
        $this->searchCriteriaBuilder = $criteriaBuilder;
    }

    /**
     * Saves links between billing frequency and products.
     *
     * @param BillingFrequency $subject
     * @param BillingFrequency $result
     * @return BillingFrequency
     */
    public function afterSave(
        BillingFrequency $subject,
        BillingFrequency $result
    ) {
        $currentLinkedProducts = $this->productBillingFrequencyRepository
            ->getListByFrequencyId($result->getId())
            ->getItems();
        foreach ($currentLinkedProducts as $currentLinkedProduct) {
            $this->productBillingFrequencyRepository->delete($currentLinkedProduct);
        }

        if ($result->getData('links') && $result->getData('links')['linked']) {
            $linkedProductData = $result->getData('links')['linked'];
            $isDefaultDataNewProducts = $this->getIsDefaultBillingFrequencyForNewProductts(
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
                $linkedProduct = $this->prepareLinkedProduct($result, $data, $maxOrder++);
                $this->productBillingFrequencyRepository->save($linkedProduct);
            }
        }

        return $result;
    }

    /**
     * Returns an array of products that don't have any billing frequency
     * to set default_billing_frequency value for these products.
     *
     * @param array $newlinkedProductData
     * @param array $currentLinkedProducts
     * @return array
     */
    private function getIsDefaultBillingFrequencyForNewProductts(
        array $newlinkedProductData,
        array $currentLinkedProducts
    ) {
        $addedProductsIds = [];
        $productsWithoutFrequencies = [];

        //Search for added to billing frequency products.
        foreach ($newlinkedProductData as $newProductData) {
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

            /** @var \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria */
            $searchCriteria = $this->searchCriteriaBuilder->create();
            /** @var ProductBillingFrequencySearchResultsInterface $foundBillingFrequencies */
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
    private function prepareLinkedProduct(
        BillingFrequency $result,
        array $data,
        $maxOrder
    ) {
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
