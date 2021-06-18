<?php

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Model\Product\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Action;

class AddDefaultValueForPurchaseType implements DataPatchInterface
{
    /**
     * @var Collection
     */
    private $productCollection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Action
     */
    private $productAction;

    /**
     * AddDefaultValueForPurchaseType constructor.
     * @param Collection $productCollection
     * @param StoreManagerInterface $storeManager
     * @param Action $productAction
     */
    public function __construct(
        Collection $productCollection,
        StoreManagerInterface $storeManager,
        Action $productAction
    )
    {
        $this->productCollection = $productCollection;
        $this->storeManager = $storeManager;
        $this->productAction = $productAction;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     *  Add default value Attribute::SUBSCRIPTION_PURCHASE_TYPE for filtering on product grid
     *
     * @return AddDefaultValueForPurchaseType|void
     */
    public function apply()
    {
        $products = $this->productCollection
            ->addFieldToFilter('type_id', ['neq' => Type::TYPE_BUNDLE])
            ->addAttributeToSelect(Attribute::SUBSCRIPTION_PURCHASE_TYPE)
            ->load();

        $ids = [];
        foreach ($products as $product) {
            $productPurchaseType = $product->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE);
            if (!isset($productPurchaseType)) {
                $ids[] = $product->getId();
            }
        }

        $stores = $this->storeManager->getStores(true);
        foreach ($stores as $store) {
            $this->productAction->updateAttributes(
                $ids,
                [Attribute::SUBSCRIPTION_PURCHASE_TYPE => PurchaseType::ONE_TIME_PURCHASE_TYPE],
                $store->getId()
            );
        }
    }
}
