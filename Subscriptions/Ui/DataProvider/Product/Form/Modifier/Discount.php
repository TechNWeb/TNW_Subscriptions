<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;

/**
* Customize Discount field
*/
class Discount extends BaseModifier
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
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        StoreManagerInterface $storeManager
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        parent::__construct($storeManager);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $discountAmountPath = $this->arrayManager->findPath(
            self::CODE_DISCOUNT_AMOUNT,
            $meta,
            null,
            'children'
        );
        $discountTypePath = $this->arrayManager->findPath(
            self::CODE_DISCOUNT_TYPE,
            $meta,
            null,
            'children'
        );

        $discountAmountContainerPath = $this->arrayManager->slicePath($discountAmountPath, 0, -2);
        $discountTypeContainerPath = $this->arrayManager->slicePath($discountTypePath, 0, -2);

        $meta = $this->arrayManager->merge(
            $discountAmountPath . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'changeComment' => 'index = ' . static::CODE_DISCOUNT_TYPE . ':value',
                ],
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-discount-amount',
                'componentType' => 'field',
                'currencySymbol' => $this->locator->getStore()->getBaseCurrency()->getCurrencySymbol(),
                'percentSymbol' => '%',
                'additionalClasses' => 'admin__field-small long_note',
                'priceFormat' => $this->getPriceFormatData(),
            ]
        );

        $meta = $this->arrayManager->merge(
            $discountAmountContainerPath . self::META_CONFIG_PATH,
            $meta,
            [
                'breakLine' => false,
                'component' => 'Magento_Ui/js/form/components/group',
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . static::CODE_FLAT_DISCOUNT . ':checked',
                    'disabled' => '!ns = ${ $.ns }, index = ' . static::CODE_FLAT_DISCOUNT . ':checked',
                ],
            ]
        );
        $meta = $this->arrayManager->set(
            $discountAmountContainerPath . '/children/' . self::CODE_DISCOUNT_TYPE,
            $meta,
            $this->arrayManager->get($discountTypePath, $meta)
        );
        $meta = $this->arrayManager->remove($discountTypeContainerPath, $meta);

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
