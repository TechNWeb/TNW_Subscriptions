<?php

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use TNW\Subscriptions\Model\ResourceModel\Eav\SubscriptionProfileAttribute;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Attribute\Collection;

/**
 * Class AddSubscriptionAttributeEntities - adds addtional data to subscription attribute type
 */
class AddSubscriptionAttributeEntities implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * AddSubscriptionAttributeEntities constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        $connection->update(
            $this->moduleDataSetup->getTable('eav_entity_type'),
            [
                'attribute_model' => SubscriptionProfileAttribute::class,
                'entity_attribute_collection' => Collection::class,
                'additional_attribute_table' => 'tnw_subscriptions_subscription_profile_entity_attribute'
            ],
            ['entity_type_code = ?' => 'subscription_profile']
        );
        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [InitData::class];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
