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
* Customize Price field
*/
class LockPrice extends AbstractModifier
{
    const CODE_LOCK_PRICE = 'tnw_subscr_lock_product_price';
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
                self::CODE_LOCK_PRICE,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'notice' =>  __('Recurring option price will always match the product price.'),
            ]
        );

        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                self::CODE_FLAT_DISCOUNT,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'notice' =>  __('Recurring option price will always match the product price.'),
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
