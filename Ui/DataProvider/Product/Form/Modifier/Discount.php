<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace  TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\Config;

/**
 * Customize Discount field
 */
class Discount extends BaseModifier
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
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     * @param Config $config
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        StoreManagerInterface $storeManager,
        Context $context,
        Config $config
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->config = $config;
        parent::__construct($storeManager, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $discountAmountPath = $this->arrayManager->findPath(
            Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT,
            $meta,
            null,
            'children'
        );
        $discountTypePath = $this->arrayManager->findPath(
            Attribute::SUBSCRIPTION_DISCOUNT_TYPE,
            $meta,
            null,
            'children'
        );

        $discountAmountContainerPath = $this->arrayManager->slicePath($discountAmountPath, 0, -2);
        $discountTypeContainerPath = $this->arrayManager->slicePath($discountTypePath, 0, -2);
        $discountAmountValue = $this->config->getDiscountAmount();

        $meta = $this->arrayManager->merge(
            $discountAmountPath . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'changeComment' => 'index = ' . Attribute::SUBSCRIPTION_DISCOUNT_TYPE . ':value',
                    'disabled' => 'index = ' . Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT . ':disabled',
                ],
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-discount-amount',
                'componentType' => 'field',
                'currencySymbol' => $this->locator->getStore()->getBaseCurrency()->getCurrencySymbol(),
                'percentSymbol' => '%',
                'additionalClasses' => 'admin__field-small long_note',
                'priceFormat' => $this->getPriceFormatData(),
                'validation' => [
                    'discount-less-then-price' => true,
                    'validate-zero-or-greater' => true,
                    'validate-number' => true,
                ],
                'default' => $discountAmountValue,
            ]
        );

        $meta = $this->arrayManager->merge(
            $discountAmountContainerPath . self::META_CONFIG_PATH,
            $meta,
            [
                'breakLine' => false,
                'component' => 'TNW_Subscriptions/js/components/discount-group',
                'imports' => [
                    'changedOfferDiscount' => 'index = ' . Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT . ':checked',
                    'changedLockPrice' => 'index = ' . Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE . ':checked',
                ],
            ]
        );

        $discountTypeValue = $this->config->getDiscountType();
        $meta = $this->arrayManager->merge(
            $discountTypePath . self::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'disabled' => 'index = ' . Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT . ':disabled',
                ],
                'default' => $discountTypeValue,
            ]
        );
        $meta = $this->arrayManager->set(
            $discountAmountContainerPath . '/children/' . Attribute::SUBSCRIPTION_DISCOUNT_TYPE,
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
