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
use Magento\Framework\App\RequestInterface;

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
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Config $config
     * @param ArrayManager $arrayManager
     * @param RequestInterface $request
     */
    public function __construct(Config $config, ArrayManager $arrayManager, RequestInterface $request)
    {
        $this->config = $config;
        $this->arrayManager = $arrayManager;
        $this->request = $request;
    }

    /**
     * Set config value as default value for "Savings calculation" on product page.
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $value = $this->config->getSavingsCalculation($this->request->getParam('store'));
        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_SAVINGS_CALCULATION,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'notice' =>  __('Product is a service and customers use it on daily basis.'),
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
