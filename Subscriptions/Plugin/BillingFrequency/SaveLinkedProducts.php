<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Plugin\BillingFrequency;

use TNW\Subscriptions\Model\BillingFrequency;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Model\ProductBillingFrequency;
use TNW\Subscriptions\Model\ProductBillingFrequencyFactory;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;

class SaveLinkedProducts
{
    /** @var ProductBillingFrequencyRepositoryInterface */
    private $productBillingFrequencyRepository;

    /** @var ProductBillingFrequencyFactory */
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

    public function afterSave(
        BillingFrequency $subject,
        BillingFrequency $result
    ) {
        $currentLinkedProducts = $this->productBillingFrequencyRepository->getListByFrequencyId($result->getId())
            ->getItems();

        foreach ($currentLinkedProducts as $currentLinkedProduct) {
            $this->productBillingFrequencyRepository->delete($currentLinkedProduct);
        }

        if ($result->getData('links') && $result->getData('links')['linked']) {
            $linkedProductData = $result->getData('links')['linked'];

            $maxOrder = 0;

            foreach ($linkedProductData as $data){
                $data['default_billing_frequency'] = $this->isDefaultBillingFrequency($data, $currentLinkedProducts);
                $linkedProduct = $this->prepareLinkedProduct($result, $data, $maxOrder++);
                $this->productBillingFrequencyRepository->save($linkedProduct);
            }
        }

        return $result;
    }

    /**
     * @param [] $data
     * @param ProductBillingFrequency $earlierLinkedProducts
     * @return int
     */
    private function isDefaultBillingFrequency($data, $earlierLinkedProducts)
    {
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
     * @param BillingFrequency $result
     * @param [] $data
     * @param int $maxOrder
     * @return ProductBillingFrequency
     */
    private function prepareLinkedProduct(
        BillingFrequency $result,
        $data,
        $maxOrder
    ) {
        $linkedProduct = $this->productBillingFrequencyFactory->create();

        $resultData = [
            ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID => $result->getId(),
            ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID => $this->prepareValue($data,'id'),
            ProductBillingFrequencyInterface::DEFAULT_BILLING_FREQUENCY => $this->prepareValue($data,'default_billing_frequency'),
            ProductBillingFrequencyInterface::PRICE => $this->prepareValue($data,'price'),
            ProductBillingFrequencyInterface::INITIAL_FEE => $this->prepareValue($data,'initial_fee'),
            ProductBillingFrequencyInterface::PRESET_QTY => $this->prepareValue($data,'preset_qty'),
            'sort_order' => $maxOrder
        ];

        $linkedProduct->setData($resultData);

        return $linkedProduct;
    }

    /**
     * @param [] $data
     * @param string $field
     * @return string
     */
    private function prepareValue($data, $field)
    {
        $result = !empty($data[$field]) ? $data[$field] : '';

        return $result;
    }
}
