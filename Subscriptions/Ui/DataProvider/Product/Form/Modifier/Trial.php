<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Product\Attribute;

/**
* Customize Trial field
*/
class Trial extends BaseModifier
{
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
        $trialLengthPath = $this->arrayManager->findPath(
            Attribute::SUBSCRIPTION_TRIAL_LENGTH,
            $meta,
            null,
            'children'
        );
        $trialLengthUnitPath = $this->arrayManager->findPath(
            Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT,
            $meta,
            null,
            'children'
        );
        $trialLengthContainerPath = $this->arrayManager->slicePath($trialLengthPath, 0, -2);
        $trialLengthUnitContainerPath = $this->arrayManager->slicePath($trialLengthUnitPath, 0, -2);

        $meta = $this->arrayManager->merge(
            $trialLengthPath . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'changeComment' => 'index = ' . Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT . ':value',
                ],
                'additionalClasses' => 'admin__field-small long_note',
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-trial-length',
                'elementTmpl' => 'TNW_Subscriptions/form/element/input',
            ]
        );
        $meta = $this->arrayManager->merge(
            $trialLengthContainerPath . self::META_CONFIG_PATH,
            $meta,
            [
                'breakLine' => false,
                'component' => 'Magento_Ui/js/form/components/group',
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':checked',
                    'disabled' => '!ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':checked',
                ],
            ]
        );
        $meta = $this->arrayManager->set(
            $trialLengthContainerPath . '/children/' . Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT,
            $meta,
            $this->arrayManager->get($trialLengthUnitPath, $meta)
        );
        $meta = $this->arrayManager->remove($trialLengthUnitContainerPath, $meta);

        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_TRIAL_PRICE,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':checked',
                    'disabled' => '!ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS. ':checked',
                    'changeComment' => 'index = price:value',
                ],
                'addbefore' => $this->locator->getStore()->getBaseCurrency()->getCurrencySymbol(),
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-price',
                'componentType' => 'field',
                'priceFormat' => $this->getPriceFormatData(),
            ]
        );

        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_TRIAL_START_DATE,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':checked',
                    'disabled' => '!ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':checked',
                ],
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-start-date',
                'componentType' => 'field',
            ]
        );

        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_START_DATE,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => '!ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':checked',
                    'disabled' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':checked',
                ],
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-start-date',
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
