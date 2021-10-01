<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\Config;

/**
 * Customize Price field
 */
class LockPrice extends AbstractModifier
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        Config $config
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $lockProductPriceValue = $this->config->getLockProductPriceStatus();
        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'notice' =>  __('Recurring option price will always match the product price.'),
                'elementTmpl' => 'TNW_Subscriptions/form/element/switcher',
                'default' => $lockProductPriceValue ? '1' : '0',
            ]
        );

        $offerFlatDiscountValue = $this->config->getOfferFlatDiscountStatus();
        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE . ':checked',
                    '__disableTmpl' => [
                        'visible' => false
                    ]
                ],
                'notice' =>  __('Apply a flat discount on top of the product price.'),
                'default' => $offerFlatDiscountValue ? '1' : '0',
            ]
        );

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }
}
