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
 * Data provider for "Set by Merchant" switcher.
 */
class SetByMerchant extends AbstractModifier
{
    const CODE_SET_BY_MERCHANT = 'tnw_subscr_set_by_merchant';

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
     * Set config value as default value for "Set By Merchant" on product page.
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $value = $this->config->setByMerchantStatus();
        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                self::CODE_SET_BY_MERCHANT,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'default' => $value ? '1' : '0',
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
