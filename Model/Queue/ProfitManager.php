<?php

namespace TNW\Subscriptions\Model\Queue;

/**
 * Class ProfitManager to publish profiles ids where need to calculate profit
 */
class ProfitManager
{
    const TOPIC_NAME = 'tnw.profile.profit';

    /**
     * ProfitManager constructor.
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        $this->publisher = $publisher;
    }

    /**
     * @param $profiles
     */
    public function setProfilesToCalculateProfit($profiles)
    {
        $this->publisher->publish(self::TOPIC_NAME, $profiles);
    }
}
