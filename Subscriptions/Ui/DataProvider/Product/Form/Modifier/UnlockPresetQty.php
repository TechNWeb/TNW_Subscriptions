<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use TNW\Subscriptions\Model\Config;

/**
 * Data provider for "Unlock preset qty" switcher.
 */
class UnlockPresetQty extends AbstractModifier
{
    const CODE_UNLOCK_PRESET_QTY = 'tnw_subscr_unlock_preset_qty';

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
     * SetByMerchant constructor.
     *
     * @param Config $config
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
        $value = $this->config->unlockPresetQtyStatus();
        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                self::CODE_UNLOCK_PRESET_QTY,
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
