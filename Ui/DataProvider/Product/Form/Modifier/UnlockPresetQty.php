<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Stdlib\ArrayManager;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Data provider for "Unlock preset qty" switcher.
 */
class UnlockPresetQty extends AbstractModifier
{
    /**
     * Subscriptions config.
     *
     * @var Config
     */
    private $config;

    /**
     * Provides methods for nested array manipulations.
     *
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @param Config $config
     * @param ArrayManager $arrayManager
     * @param LocatorInterface $locator
     */
    public function __construct(Config $config, ArrayManager $arrayManager, LocatorInterface $locator)
    {
        $this->config = $config;
        $this->arrayManager = $arrayManager;
        $this->locator = $locator;
    }

    /**
     * Set config value as default value for "Unlock preset qty" on product page.
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        if ($this->locator->getProduct()->getTypeId() === Configurable::TYPE_CODE) {
            $config = [
                'disabled' => true,
                'default' => '0',
                'notice' =>  __('Preset qty can be unlocked and set in child product only'),
            ];
        } else {
            $value = $this->config->getUnlockPresetQtyStatus();
            $config = [
                'default' => $value ? '1' : '0',
                'notice' => __('Product quantity is preset for the customer and cannot be changed.'),
            ];
        }
        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            $config
        );

        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }
}
