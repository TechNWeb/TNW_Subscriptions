<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;

/**
* Customize Discount field
*/
class Discount extends AbstractModifier
{
    const CODE_DISCOUNT_TYPE = 'tnw_subscr_discount_type';
    const CODE_DISCOUNT_AMOUNT = 'tnw_subscr_discount_amount';
    const CODE_FLAT_DISCOUNT = 'tnw_subscr_offer_flat_discount';

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                self::CODE_DISCOUNT_AMOUNT,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . static::CODE_FLAT_DISCOUNT . ':checked',
                    'disabled' => '!ns = ${ $.ns }, index = ' . static::CODE_FLAT_DISCOUNT . ':checked',
                ],
                'currencySymbol' => $this->locator->getStore()->getBaseCurrency()->getCurrencySymbol(),
                'percentSymbol' => '%',
            ]
        );

        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                self::CODE_DISCOUNT_TYPE,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . static::CODE_FLAT_DISCOUNT . ':checked',
                    'disabled' => '!ns = ${ $.ns }, index = ' . static::CODE_FLAT_DISCOUNT . ':checked',
                    'changeComment' => 'index = ' . static::CODE_DISCOUNT_AMOUNT . ':value',
                ],
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-discount-type',
                'componentType' => 'field',
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
