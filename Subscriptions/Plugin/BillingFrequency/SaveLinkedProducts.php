<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Plugin\BillingFrequency;

use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Model\BillingFrequency;
use TNW\Subscriptions\Model\ProductBillingFrequencyFactory;

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
     * SaveLinkedProducts constructor.
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository
     * @param ProductBillingFrequencyFactory $productBillingFrequencyFactory
     */
    public function __construct(
        ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository,
        ProductBillingFrequencyFactory $productBillingFrequencyFactory
    ) {
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->productBillingFrequencyFactory = $productBillingFrequencyFactory;
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
            $maxOrder = 0;
            foreach ($linkedProductData as $data) {
                $data['default_billing_frequency'] = $this->isDefaultBillingFrequency($data, $currentLinkedProducts);
                $linkedProduct = $this->prepareLinkedProduct($result, $data, $maxOrder++);
                $this->productBillingFrequencyRepository->save($linkedProduct);
            }
        }

        return $result;
    }

    /**
     * @param array $data
     * @param ProductBillingFrequencyInterface[] $earlierLinkedProducts
     * @return int
     */
    private function isDefaultBillingFrequency(
        array $data,
        $earlierLinkedProducts
    ) {
        $isDefault = 0;

        foreach ($earlierLinkedProducts as $linkedProduct) {
            if ($data['id'] == $linkedProduct->getMagentoProductId()) {
                $isDefault = $linkedProduct->getDefaultBillingFrequency();
                break;
            }
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
            ProductBillingFrequencyInterface::DEFAULT_BILLING_FREQUENCY => $this->prepareValue($data,
                'default_billing_frequency'),
            ProductBillingFrequencyInterface::PRICE => $this->prepareValue($data, 'price'),
            ProductBillingFrequencyInterface::INITIAL_FEE => $this->prepareValue($data, 'initial_fee'),
            ProductBillingFrequencyInterface::PRESET_QTY => $this->prepareValue($data, 'preset_qty'),
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
