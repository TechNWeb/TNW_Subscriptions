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

        $quote = $this->quoteGenerator->generateProfileQuote($profile);

        $totals = $quote->getTotals();
        if ($profile->getStatus() == ProfileStatus::STATUS_TRIAL) {
            $profileProducts = array_values($profile->getProfileProducts());
            $profileProduct = array_shift($profileProducts);
            $price = $profileProduct->getPrice();
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
