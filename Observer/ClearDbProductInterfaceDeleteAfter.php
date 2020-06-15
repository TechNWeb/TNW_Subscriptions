<?php


namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
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
     * ClearDbProductInterfaceDeleteAfter constructor.
     * @param ProductBillingFrequency $productBillFrequency
     * @param ProductSubscriptionProfile $productSubscriptionProfile
     */
    public function __construct(
        ProductBillingFrequency $productBillFrequency,
        ProductSubscriptionProfile $productSubscriptionProfile
    )
    {
        $this->productBillFrequency = $productBillFrequency;
        $this->productSubscriptionProfile = $productSubscriptionProfile;
    }

    /**
     * Clear product subscribe profile and product billing frequency table after delete product
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $entity = $observer->getEvent()->getEntityId();

        $productsSubscriptionProfile = $this->productSubscriptionProfile->getItemsByColumnValue(
            'magento_product_id', $entity->getEntityId()
        );
        foreach ($productsSubscriptionProfile as $item) {
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
