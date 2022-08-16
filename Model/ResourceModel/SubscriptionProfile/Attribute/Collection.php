<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Attribute;

/**
 * Subscription profile attribute resource collection
 */
class Collection extends \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
{
    /**
     * Entity factory
     *
     * @var \Magento\Eav\Model\EntityFactory
     */
    protected $eavEntityFactory;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->eavEntityFactory = $eavEntityFactory;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $eavConfig, $connection, $resource);
    }

    /**
     * Resource model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \TNW\Subscriptions\Model\ResourceModel\Eav\SubscriptionProfileAttribute::class,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute::class
        );
    }

    /**
     * @return $this|\Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initSelect()
    {
        $entityTypeId = (int)$this->eavEntityFactory->create()->setType(
            \TNW\Subscriptions\Model\SubscriptionProfile::ENTITY
        )->getTypeId();
        $columns = $this->getConnection()->describeTable($this->getResource()->getMainTable());
        $additionalColumns = $this->getConnection()
            ->describeTable($this->getTable('tnw_subscriptions_subscription_profile_entity_attribute'));
        unset($additionalColumns['attribute_id']);
        $retColumns = [];
        foreach ($columns as $labelColumn => $columnData) {
            $retColumns[$labelColumn] = $labelColumn;
            if ($columnData['DATA_TYPE'] == \Magento\Framework\DB\Ddl\Table::TYPE_TEXT) {
                $retColumns[$labelColumn] = 'main_table.' . $labelColumn;
            }
        }
        $retColumnsAdditional = [];
        foreach ($additionalColumns as $labelColumn => $columnData) {
            $retColumnsAdditional[$labelColumn] = $labelColumn;
        }
        $this->getSelect()->from(
            ['main_table' => $this->getResource()->getMainTable()],
            $retColumns
        )->joinLeft(
            ['additional_table' => $this->getTable('tnw_subscriptions_subscription_profile_entity_attribute')],
            'additional_table.attribute_id = main_table.attribute_id',
            $retColumnsAdditional
        )->where(
            'main_table.entity_type_id = ?',
            $entityTypeId
        );
        return $this;
    }

    /**
     * Specify attribute entity type filter.
     * Entity type is defined.
     *
     * @param  int $typeId
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setEntityTypeFilter($typeId)
    {
        return $this;
    }

    /**
     * Return array of fields to load attribute values
     *
     * @return string[]
     */
    protected function _getLoadDataFields()
    {
        $fields = array_merge(
            parent::_getLoadDataFields(),
            [
                'additional_table.is_wysiwyg_enabled',
                'additional_table.is_pagebuilder_enabled',
                'additional_table.is_visible_on_front'
            ]
        );

        return $fields;
    }
}
