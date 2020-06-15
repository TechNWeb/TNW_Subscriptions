<?php


namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as MagentoProductCollection;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency\Collection as ProductBillingFrequency;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile\Collection as ProductSubscriptionProfile;

class ClearDbProductInterfaceDeleteAfter implements ObserverInterface
{
    /**
     * @var $productBillFrequency
     */
    protected $productBillFrequency;

    /**
     * @var ProductSubscriptionProfile
     */
    protected $productSubscriptionProfile;

    /**
     * @var MagentoProductCollection
     */
    protected $magentoProductCollection;

    /**
     * ClearDbProductInterfaceDeleteAfter constructor.
     * @param ProductBillingFrequency $productBillFrequency
     * @param ProductSubscriptionProfile $productSubscriptionProfile
     * @param MagentoProductCollection $magentoProductCollection
     */
    public function __construct(
        ProductBillingFrequency $productBillFrequency,
        ProductSubscriptionProfile $productSubscriptionProfile,
        MagentoProductCollection $magentoProductCollection
    )
    {
        $this->productBillFrequency = $productBillFrequency;
        $this->productSubscriptionProfile = $productSubscriptionProfile;
        $this->magentoProductCollection = $magentoProductCollection;
    }

    /**
     * Clear product subscribe profile and product billing frequency table after delete product
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $entity = $observer->getEvent()->getEntity();

        $getMagentoProducts = $this->magentoProductCollection->getItemsByColumnValue(
            'entity_id', $entity->getEntityId()
        );
        if(count($getMagentoProducts) > 1) {
            $productSubscriptions = $this->productSubscriptionProfile->getItemsByColumnValue(
                'magento_product_id', $entity->getEntityId()
            );
            foreach ($productSubscriptions as $item) {
                $item->delete();
            }
            $productsBillFrequency = $this->productBillFrequency->getItemsByColumnValue(
                'magento_product_id', $entity->getEntityId()
            );
            foreach ($productsBillFrequency as $item) {
                $item->delete();
            }
        }
    }
}
