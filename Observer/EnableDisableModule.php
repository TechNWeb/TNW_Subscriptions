<?php
/**
 * Copyright © 2020 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Config\Model\ResourceModel\ConfigFactory;

/**
 * Class EnableDisableModule - used to additional config processing on enabling and disabling Subscriptions in config
 */
class EnableDisableModule implements ObserverInterface
{
    /**
     * @var ConfigFactory
     */
    private $resourceConfigFactory;

    /**
     * EnableDisableModule constructor.
     * @param ConfigFactory $resourceConfigFactory
     */
    public function __construct(
        ConfigFactory $resourceConfigFactory
    ) {
        $this->resourceConfigFactory = $resourceConfigFactory;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $configData = $observer->getEvent()->getDataByKey('configData');
        if (is_array($configData)
            && array_key_exists('section', $configData)
            && $configData['section'] == 'tnw_subscriptions_general'
        ) {
            $configPath = 'advanced/modules_disable_output/TNW_Subscriptions';
            if ($configData['website']) {
                $scope = 'websites';
                $scopeId = $configData['website'];
            } else {
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                $scopeId = 0;
            }
            if (isset($configData['groups']['general']['fields']['active']['value'])) {
                $state = $configData['groups']['general']['fields']['active']['value'] ? 0 : 1;
                $this->resourceConfigFactory->create()->saveConfig(
                    $configPath,
                    $state,
                    $scope,
                    $scopeId
                );
            } elseif (isset($configData['groups']['general']['fields']['active']['inherit'])
                && $configData['groups']['general']['fields']['active']['inherit']
                && $configData['website']
            ) {
                $this->resourceConfigFactory->create()->deleteConfig(
                    $configPath,
                    $scope,
                    $scopeId
                );
            }
        }
    }
}
