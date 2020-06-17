<?php


namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as MagentoProductCollection;
use Magento\Framework\Exception\CouldNotDeleteException;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency\Collection as ProductBillingFrequency;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile\Collection as ProductSubscriptionProfile;

class ClearDbProductInterfaceDeleteAfter implements ObserverInterface
{
    /**
     * @var $productBillFrequency
     */
    protected $productBillFrequencyCollection;

    /**
     * @var ProductSubscriptionProfile
     */
    protected $productSubscriptionProfileCollection;

    /**
     * @var MagentoProductCollection
     */
    protected $magentoProductCollection;

    /**
     * ClearDbProductInterfaceDeleteAfter constructor.
     * @param ProductBillingFrequency $productsBillFrequency
     * @param ProductSubscriptionProfile $productsSubscriptionProfile
     * @param MagentoProductCollection $magentoProductCollection
     */
    public function __construct(
        ProductBillingFrequency $productsBillFrequency,
        ProductSubscriptionProfile $productsSubscriptionProfile,
        MagentoProductCollection $magentoProductCollection
    )
    {
        $this->productBillFrequencyCollection = $productsBillFrequency;
        $this->productSubscriptionProfileCollection = $productsSubscriptionProfile;
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

        if (!$this->magentoProductCollection->getItemsByColumnValue('entity_id', $entity->getEntityId())) {
            try {
                $productSubscriptions = $this->productSubscriptionProfileCollection->getItemsByColumnValue(
                    'magento_product_id', $entity->getEntityId()
                );
                foreach ($productSubscriptions as $item) {
                    $item->delete();
                }
                $productsBillFrequency = $this->productBillFrequencyCollection->getItemsByColumnValue(
                    'magento_product_id', $entity->getEntityId()
                );
                foreach ($productsBillFrequency as $item) {
                    $item->delete();
                }
            } catch (\Exception $exception) {
                throw new CouldNotDeleteException(__($exception->getMessage()));
            }
        }
    }
}
