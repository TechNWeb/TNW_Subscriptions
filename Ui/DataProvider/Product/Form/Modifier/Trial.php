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
        $trialStatusPath = $this->arrayManager->findPath(
            Attribute::SUBSCRIPTION_TRIAL_STATUS,
            $meta,
            null,
            'children'
        );
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

        $trialLengthValue = $this->config->getTrialLength();
        $meta = $this->arrayManager->merge(
            $trialStatusPath . static::META_CONFIG_PATH,
            $meta,
            [
                'component' => 'TNW_Subscriptions/js/components/trial-status',
                'imports' => [
                    'disabled'=> 'ns = ${ $.ns }, index = shipment_type:value', //In case of bundle product
                    'onPriceTypeChange' => 'ns = ${ $.ns }, index = price_type:value',
                    '__disableTmpl' => [
                        'disabled' => false,
                        'onPriceTypeChange' => false
                    ]
                ]
            ]
        );
        $meta = $this->arrayManager->merge(
            $trialLengthPath . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'changeComment' => 'index = ' . Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT . ':value',
                    'disabled' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':disabled',
                    '__disableTmpl' => [
                        'disabled' => false
                    ]
                ],
                'additionalClasses' => 'admin__field-small long_note',
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-trial-length',
                'elementTmpl' => 'TNW_Subscriptions/form/element/render-binding-input',
                'default' => $trialLengthValue,
                'validation' => [
                    'validate-zero-or-greater' => true,
                    'validate-number' => true,
                    'validate-digits' => true,
                ],
            ]
        );
        $meta = $this->arrayManager->merge(
            $trialLengthContainerPath . self::META_CONFIG_PATH,
            $meta,
            [
                'breakLine' => false,
                'component' => 'Magento_Ui/js/form/components/group',
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, dataScope = ${ $.parentScope }.product.' .
                        Attribute::SUBSCRIPTION_TRIAL_STATUS . ':checked',
                    '__disableTmpl' => [
                        'visible' => false
                    ]
                ],
            ]
        );

        $trialLengthUnitPathValue = $this->config->getTrialLengthUnit();
        $meta = $this->arrayManager->merge(
            $trialLengthUnitPath . self::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'disabled' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS. ':disabled',
                    '__disableTmpl' => [
                        'disabled' => false
                    ]
                ],
                'default' => $trialLengthUnitPathValue,
            ]
        );
        // Move trial unit to trial length container to make them inline
        $meta = $this->arrayManager->set(
            $trialLengthContainerPath . '/children/' . Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT,
            $meta,
            $this->arrayManager->get($trialLengthUnitPath, $meta)
        );
        // Remove trial unit container
        $meta = $this->arrayManager->remove($trialLengthUnitContainerPath, $meta);

        $trialPriceValue = $this->config->getTrialPrice();
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
                    'disabled' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS. ':disabled',
                    'changeComment' => 'index = price:value',
                    '__disableTmpl' => [
                        'visible' => false,
                        'disabled' => false
                    ]
                ],
                'validation' => [
                    'validate-number' => true,
                ],
                'addbefore' => $this->locator->getStore()->getBaseCurrency()->getCurrencySymbol(),
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-price',
                'componentType' => 'field',
                'priceFormat' => $this->getPriceFormatData(),
                'default' => $trialPriceValue,
            ]
        );

        $trialStartDateValue = $this->config->getTrialStartDateType();
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
                    'disabled' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':disabled',
                    '__disableTmpl' => [
                        'visible' => false,
                        'disabled' => false
                    ]
                ],
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-start-date',
                'componentType' => 'field',
                'default' => $trialStartDateValue,
            ]
        );

        $startDateValue = $this->config->getStartDateType();
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
                    '__disableTmpl' => [
                        'visible' => false
                    ]
                ],
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-start-date',
                'componentType' => 'field',
                'default' => $startDateValue,
            ]
        );

        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_TRIAL_CAN_SKIP,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':checked',
                    'disabled' => 'ns = ${ $.ns }, index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS. ':disabled',
                    '__disableTmpl' => [
                        'visible' => false,
                        'disabled' => false
                    ]
                ],
                'component' => 'Magento_Ui/js/form/element/single-checkbox',
                'componentType' => 'field',
            ]
        );

        $trialStatusValue = $this->config->getTrialStatus();
        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                Attribute::SUBSCRIPTION_TRIAL_STATUS,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'default' => $trialStatusValue ? '1' : '0',
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
