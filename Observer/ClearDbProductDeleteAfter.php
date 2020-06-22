<?php


namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as MagentoProductCollection;
use Magento\Framework\Exception\CouldNotDeleteException;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency\Collection as ProductBillingFrequency;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile\Collection as ProductSubscriptionProfile;

class ClearDbProductDeleteAfter implements ObserverInterface
{
    /**
     * @var $productBillFrequency
     */
    protected $productBillFrequencyCollection;

    /**
     * @var $productSubscriptionProfileCollection
     */
    protected $productSubscriptionProfileCollection;

    /**
     * @var $magentoProductCollection
     */
    protected $magentoProductCollection;

    /**
     * @var $historyLogger
     */
    protected $historyLogger;

    /**
     * ClearDbProductInterfaceDeleteAfter constructor.
     * @param ProductBillingFrequency $productsBillFrequency
     * @param ProductSubscriptionProfile $productsSubscriptionProfile
     * @param MagentoProductCollection $magentoProductCollection
     * @param MessageHistoryLogger $historyLogger
     */
    public function __construct(
        ProductBillingFrequency $productsBillFrequency,
        ProductSubscriptionProfile $productsSubscriptionProfile,
        MagentoProductCollection $magentoProductCollection,
        MessageHistoryLogger $historyLogger
    )
    {
        $this->productBillFrequencyCollection = $productsBillFrequency;
        $this->productSubscriptionProfileCollection = $productsSubscriptionProfile;
        $this->magentoProductCollection = $magentoProductCollection;
        $this->historyLogger = $historyLogger;
    }

    /**
     * Clear product subscribe profile and product billing frequency table after delete product
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $entity = $observer->getEvent()->getEntity();
        $product = $this->magentoProductCollection->getItemsByColumnValue('entity_id', $entity->getEntityId());
        if (!$product) {
            try {
                $productSubscriptions = $this->productSubscriptionProfileCollection->getItemsByColumnValue(
                    'magento_product_id', $entity->getEntityId()
                );
                foreach ($productSubscriptions as $item) {
                    $this->historyLogger->log(
                        __('Product %1 has been removed.', $item->getName()),
                        $item->getSubscriptionProfileId()
                    );
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
