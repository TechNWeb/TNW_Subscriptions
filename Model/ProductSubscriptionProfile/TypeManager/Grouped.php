<?php

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager;

use Magento\Framework\Pricing\SaleableInterface;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Model\Product\Attribute;

class Grouped extends Base
{
    /**
     * @inheritDoc
     */
    public function modifyBuyRequests(array $products)
    {
        // TODO: Implement modifyBuyRequests() method.
    }

    public function getProductDataObject(SaleableInterface $product, array $arguments = null)
    {
        $data = parent::getProductDataObject($product, $arguments);
        $groupedPurchaseTypes = [];
        foreach ($this->getChildProducts($product) as $child) {
            $groupedPurchaseTypes[$child->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE)][] = $child;
        }
        if (!empty($groupedPurchaseTypes[PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE])
            || (!empty($groupedPurchaseTypes[PurchaseType::RECURRING_PURCHASE_TYPE])
                && !empty($groupedPurchaseTypes[PurchaseType::ONE_TIME_PURCHASE_TYPE]))
        ) {
            $groupedPurchaseType = PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE;
        } elseif (!empty($groupedPurchaseTypes[PurchaseType::RECURRING_PURCHASE_TYPE])) {
            $groupedPurchaseType = PurchaseType::RECURRING_PURCHASE_TYPE;
        } else {
            $groupedPurchaseType = PurchaseType::ONE_TIME_PURCHASE_TYPE;
        }
        $data->setData(Attribute::SUBSCRIPTION_PURCHASE_TYPE, $groupedPurchaseType);
        return $data;
    }

    public function getChildProducts(SaleableInterface $product)
    {
        $childrenIds = $product->getTypeInstance()->getChildrenIds($product->getId());
        return $this->productRepository->getList(
            $this->searchCriteriaBuilder->addFilter('entity_id', $childrenIds, 'in')->create()
        )->getItems();
    }
}
