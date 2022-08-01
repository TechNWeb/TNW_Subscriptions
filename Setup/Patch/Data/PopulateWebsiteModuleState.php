<?php

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Config\Model\ResourceModel\ConfigFactory;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * Class PopulateWebsiteModuleState - data patch to disable or enable module output on websties
 */
class PopulateWebsiteModuleState implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var CollectionFactory
     */
    private $configCollection;

    /**
     * PopulateWebsiteModuleState constructor.
     *
     * @param ConfigFactory $configFactory
     * @param CollectionFactory $configCollection
     */
    public function __construct(
        ConfigFactory $configFactory,
        CollectionFactory $configCollection
    ) {
        $this->configCollection = $configCollection;
        $this->configFactory = $configFactory;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        $outputPath = 'advanced/modules_disable_output/TNW_Subscriptions';
        $activePath = 'tnw_subscriptions_general/general/active';
        $configCollection = $this->configCollection->create()
            ->addFieldToFilter('path', $activePath);
        foreach ($configCollection->getItems() as $config) {
            $this->configFactory->create()->saveConfig(
                $outputPath,
                $config->getValue() ? 0 : 1,
                $config->getScope(),
                $config->getScopeId()
            );
        }
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
