<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Data provider for "Savings calculation" switcher.
 */
class SavingsCalculation extends AbstractModifier
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
     * Set config value as default value for "Savings calculation" on product page.
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $value = $this->config->getSavingsCalculation();
        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_SAVINGS_CALCULATION,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'notice' =>  __('Product is a service that my customers use on daily basis.'),
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
