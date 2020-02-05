<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
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
     * @param Config $config
     * @param ArrayManager $arrayManager
     */
    public function __construct(Config $config, ArrayManager $arrayManager)
    {
        $this->config = $config;
        $this->arrayManager = $arrayManager;
    }

    /**
     * Set config value as default value for "Unlock preset qty" on product page.
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $value = $this->config->getUnlockPresetQtyStatus();
        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'default' => $value ? '1' : '0',
                'notice' =>  __('Product quantity is preset for the customer and cannot be changed.'),
            ]
        );

        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_HIDE_QTY,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY . ':checked',
                    'disabled' => '!ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY . ':checked',
                ],
                'notice' =>  __('Product quantity is hidden for the customer and cannot be changed.'),
            ]
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
