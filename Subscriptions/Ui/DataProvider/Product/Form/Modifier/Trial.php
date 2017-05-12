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
* Customize Trial field
*/
class Trial extends AbstractModifier
{
    const CODE_TRIAL = 'tnw_subscr_trial_status';
    const CODE_TRIAL_LENGTH = 'tnw_subscr_trial_length';
    const CODE_TRIAL_LENGTH_UNIT = 'tnw_subscr_trial_length_unit';
    const CODE_TRIAL_PRICE = 'tnw_subscr_trial_price';
    const CODE_TRIAL_START_DATE = 'tnw_subscr_trial_start_date';
    const CODE_START_DATE = 'tnw_subscr_start_date';

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
                self::CODE_TRIAL_LENGTH,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . static::CODE_TRIAL . ':checked',
                    'disabled' => '!ns = ${ $.ns }, index = ' . static::CODE_TRIAL . ':checked',
                ]
            ]
        );

        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                self::CODE_TRIAL_LENGTH_UNIT,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . static::CODE_TRIAL . ':checked',
                    'disabled' => '!ns = ${ $.ns }, index = ' . static::CODE_TRIAL . ':checked',
                ]
            ]
        );

        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                self::CODE_TRIAL_PRICE,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . static::CODE_TRIAL . ':checked',
                    'disabled' => '!ns = ${ $.ns }, index = ' . static::CODE_TRIAL . ':checked',
                ]
            ]
        );

        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                self::CODE_TRIAL_START_DATE,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => 'ns = ${ $.ns }, index = ' . static::CODE_TRIAL . ':checked',
                    'disabled' => '!ns = ${ $.ns }, index = ' . static::CODE_TRIAL . ':checked',
                ]
            ]
        );

        $meta = $this->arrayManager->merge(
            $this->arrayManager->findPath(
                self::CODE_START_DATE,
                $meta,
                null,
                'children'
            ) . static::META_CONFIG_PATH,
            $meta,
            [
                'imports' => [
                    'visible' => '!ns = ${ $.ns }, index = ' . static::CODE_TRIAL . ':checked',
                    'disabled' => 'ns = ${ $.ns }, index = ' . static::CODE_TRIAL . ':checked',
                ]
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
