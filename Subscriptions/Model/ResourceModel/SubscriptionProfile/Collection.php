<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile;

use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Eav\Model\EntityFactory as ModelEntityFactory;
use Magento\Eav\Model\ResourceModel\Helper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory as CollectionEntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Validator\UniversalFactory;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile;

/**
 * Collection class for Subscription Profile model.
 */
class Collection extends AbstractCollection
{
    /**
     * @param CollectionEntityFactory $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param Config $eavConfig
     * @param ResourceConnection $resource
     * @param ModelEntityFactory $eavEntityFactory
     * @param Helper $resourceHelper
     * @param UniversalFactory $universalFactory
     * @param AdapterInterface $connection
     * @param SubscriptionProfile $subscriptionProfile
     */
    public function __construct(
        CollectionEntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Config $eavConfig,
        ResourceConnection $resource,
        ModelEntityFactory $eavEntityFactory,
        Helper $resourceHelper,
        UniversalFactory $universalFactory,
        AdapterInterface $connection,
        SubscriptionProfile $subscriptionProfile
    ) {
        $this->_entity = $subscriptionProfile;

        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $connection
        );
    }
}
