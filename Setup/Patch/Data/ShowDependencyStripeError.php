<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Setup\Patch\Data;

use Laminas\Json\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use TNW\Subscriptions\Setup\SubscriptionSetupFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Component\ComponentRegistrar;
use TNW\Subscriptions\Model\Config;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ShowDependencyStripeError - patch to check the dependency to stripe version if exists
 */
class ShowDependencyStripeError implements DataPatchInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * ShowDependencyStripeError constructor.
     * @param Filesystem $filesystem
     * @param ComponentRegistrar $componentRegistrar
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Filesystem $filesystem,
        ComponentRegistrar $componentRegistrar,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->componentRegistrar = $componentRegistrar;
        $this->filesystem = $filesystem;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return DataPatchInterface|void
     * @throws LocalizedException
     */
    public function apply()
    {
        $stripeEnabled = false;
        foreach ($this->storeManager->getWebsites(true) as $website) {
            if ($this->config->isPaymentAvailable('tnw_stripe', $website->getId())) {
                $stripeEnabled = true;
                break;
            }
        }
        if ($stripeEnabled && $this->getComposerDataVersion() < '2.3.17') {
            throw new LocalizedException(__('Need to upgrade TNW_Stripe extension to 2.3.17 or newer.'));
        }
    }

    /**
     * @return string
     */
    public function getComposerDataVersion()
    {
        $version = '2.3.17';
        $path = $this->componentRegistrar->getPath(
            ComponentRegistrar::MODULE,
            'TNW_Stripe'
        );
        if (!$path) {
            return $version;
        }
        try {
            $json = $this->filesystem->getDirectoryReadByPath($path)->readFile('composer.json');
            $data = Json::decode($json);
            if (array_key_exists('version', $data)) {
                $version = $data['version'];
            }
        } catch (\Throwable $e) {
            $version = '2.3.17';
        }

        return $version;
    }
}
