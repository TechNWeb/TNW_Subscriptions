<?php
/**
 * Created by PhpStorm.
 * User: eermolaev
 * Date: 2019-02-01
 * Time: 17:47
 */

namespace TNW\Subscriptions\Observer\Save;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

class SubscriptionProfile implements ObserverInterface
{

    /**
     * @var \TNW\Subscriptions\Cron\Quote\Creator
     */
    private $quoteGenerator;

    /**
     * Repository for saving/retrieving subscription profiles.
     *
     * @var SubscriptionProfileRepository
     */
    private $subscriptionProfileRepository;

    /**
     * SubscriptionProfile constructor.
     * @param \TNW\Subscriptions\Cron\Quote\Creator $quoteGenerator
     */
    public function __construct(
        \TNW\Subscriptions\Cron\Quote\Creator $quoteGenerator,
        SubscriptionProfileRepository $subscriptionProfileRepository
    )
    {
        $this->quoteGenerator = $quoteGenerator;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $profile = $observer->getData('data_object');

        $profilePayment = $profile->getPayment();

        if ($profilePayment->dataHasChangedFor('payment_additional_info') && $profilePayment->getSentMail() != 0) {
            $profilePayment->setSentMail(0)->save();
        }

        $quote = $this->quoteGenerator->generateProfileQuote($profile);

        $totals = $quote->getTotals();

        $quoteItem = $quote->getItemsCollection()->getFirstItem();
        $price = $quoteItem->getPrice() * $quoteItem->getQty();
        $profileProducts = $profile->getProfileProducts();
        $profileProduct = array_shift($profileProducts);
        $profileProduct->setPrice($price);

        if ($profile->getStatus() == ProfileStatus::STATUS_TRIAL) {
            $totals['grand_total']->setValue(
                $totals['grand_total']->getValue() - $totals['subtotal']->getValue() + $price
            );
            $totals['subtotal']->setValue($price);
        }

        foreach ($totals as $total) {
            $profile->setData($total->getCode(), $total->getValue());
        }
    }
}
