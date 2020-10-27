<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel;

use Magento\Framework\Api\ExtensionAttribute;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\SimpleDataObjectConverter;

class ExtensionAttributes
{
    /**
     * @var \Magento\Framework\Api\ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessor
     */
    private $joinProcessor;

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessorHelper
     */
    private $joinProcessorHelper;

    public function __construct(
        ExtensionAttributesFactory $extensionAttributesFactory,
        ExtensionAttribute\JoinProcessor $joinProcessor,
        ExtensionAttribute\JoinProcessorHelper $joinProcessorHelper
    ) {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->joinProcessor = $joinProcessor;
        $this->joinProcessorHelper = $joinProcessorHelper;
    }

    /**
     * @param $extensibleClassName
     *
     * @return array
     */
    private function joinConfig($extensibleClassName)
    {
        $extensibleInterfaceName = $this->extensionAttributesFactory->getExtensibleInterfaceName($extensibleClassName);
        $extensibleInterfaceName = ltrim($extensibleInterfaceName, '\\');

        $config = $this->joinProcessorHelper->getConfigData();
        if (!isset($config[$extensibleInterfaceName])) {
            return [];
        }

        $cartAttributeConfig = $config[$extensibleInterfaceName];
        if (array_key_exists('negotiable_quote', $cartAttributeConfig)) {
            unset($cartAttributeConfig['negotiable_quote']);
        }
        return array_filter(array_map(function ($attributeConfig) {
            if (empty($attributeConfig[ExtensionAttribute\Config\Converter::JOIN_DIRECTIVE])) {
                return false;
            }

            return $attributeConfig[ExtensionAttribute\Config\Converter::JOIN_DIRECTIVE];
        }, $cartAttributeConfig));
    }

    /**
     * @param \Magento\Framework\Model\AbstractExtensibleModel $entity
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    public function load($entity)
    {
        $extensibleEntityClass = \get_class($entity);

        $joinConfig = $this->joinConfig($extensibleEntityClass);
        if (empty($joinConfig)) {
            return;
        }

        $resource = $entity->getResource();
        $connection = $resource->getConnection();
        $select = $connection->select()
            ->from(['main_table' => $resource->getMainTable()], [])
            ->where(sprintf('main_table.%s = ?', $resource->getIdFieldName()), $entity->getId());

        foreach ($joinConfig as $attributeCode => $joinData) {
            $selectFields = $this->joinProcessorHelper
                ->getSelectFieldsMap($attributeCode, $joinData[ExtensionAttribute\Config\Converter::JOIN_FIELDS]);

            $columns = [];
            foreach ($selectFields as $selectField) {
                $columns[$selectField[ExtensionAttribute\JoinDataInterface::SELECT_FIELD_INTERNAL_ALIAS]]
                    = $selectField[ExtensionAttribute\JoinDataInterface::SELECT_FIELD_WITH_DB_PREFIX];
            }

            $tableAlias = $this->joinProcessorHelper->getReferenceTableAlias($attributeCode);
            $select->joinLeft(
                [$tableAlias => $resource->getTable($joinData[ExtensionAttribute\Config\Converter::JOIN_REFERENCE_TABLE])],
                sprintf(
                    'main_table.%s = %s.%s',
                    $joinData[ExtensionAttribute\Config\Converter::JOIN_ON_FIELD],
                    $tableAlias,
                    $joinData[ExtensionAttribute\Config\Converter::JOIN_REFERENCE_FIELD]
                ),
                $columns
            );
        }

        $columns = $select->getPart(\Zend_Db_Select::COLUMNS);
        if (empty($columns)) {
            return;
        }
        $dataSelect = $connection->fetchRow($select);
        if (!$dataSelect) {
            $dataSelect = [];
        }
        $data = $this->joinProcessor->extractExtensionAttributes(
            $extensibleEntityClass,
            $dataSelect
        );

        if (empty($data[$entity::EXTENSION_ATTRIBUTES_KEY])) {
            return;
        }

        $entity->setExtensionAttributes($data[$entity::EXTENSION_ATTRIBUTES_KEY]);
    }

    /**
     * @param \Magento\Framework\Model\AbstractExtensibleModel $entity
     */
    public function save($entity)
    {
        $extensibleEntityClass = \get_class($entity);

        $joinConfig = $this->joinConfig($extensibleEntityClass);
        if (empty($joinConfig)) {
            return;
        }

        $extensionAttributes = $entity->getExtensionAttributes();
        if (!$extensionAttributes instanceof \Magento\Framework\Api\ExtensionAttributesInterface) {
            return;
        }

        foreach ($joinConfig as $attributeCode => $joinData) {
            $resource = $entity->getResource();
            $connection = $resource->getConnection();

            $selectFields = $joinData[ExtensionAttribute\Config\Converter::JOIN_FIELDS];

            $getterName = sprintf('get%s', SimpleDataObjectConverter::snakeCaseToCamelCase($attributeCode));
            $value = $extensionAttributes->$getterName();

            $bind = [];
            foreach ($selectFields as $selectField) {
                $internalFieldName = $selectField[ExtensionAttribute\Config\Converter::JOIN_FIELD_COLUMN]
                    ?: $selectField[ExtensionAttribute\Config\Converter::JOIN_FIELD];

                switch (true) {
                    case \is_scalar($value):
                        $bind[$internalFieldName] = $value;
                        break;

                    case \is_object($value):
                        $getterName = sprintf(
                            'get%s',
                            SimpleDataObjectConverter::snakeCaseToCamelCase(
                                $selectField[ExtensionAttribute\Config\Converter::JOIN_FIELD]
                            )
                        );

                        $bind[$internalFieldName] = $value->$getterName();
                        break;
                }
            }

            if (empty($bind)) {
                continue;
            }

            $indexName = $joinData[ExtensionAttribute\Config\Converter::JOIN_REFERENCE_FIELD];
            $tableName = $resource->getTable($joinData[ExtensionAttribute\Config\Converter::JOIN_REFERENCE_TABLE]);
            $where = $connection->prepareSqlCondition($indexName, $entity->getId());

            $select = $connection->select()
                ->from($tableName, [$indexName])
                ->where($where);

            if ($connection->fetchOne($select) !== false) {
                $connection->update($tableName, $bind, $where);
            } else {
                $bind[$indexName] = $entity->getId();
                $connection->insert($tableName, $bind);
            }
        }
    }
}
