<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\ArrayManager;
use TNW\Subscriptions\Model\Context;

/**
 * Customize Donation field
 */
class Donation extends BaseModifier
{
    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * Donation constructor.
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        StoreManagerInterface $storeManager,
        Context $context
    ) {
        parent::__construct($storeManager, $context);
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        if ($this->locator->getProduct()->getTypeId() !== 'donation') {
            return $meta;
        }

        $schedulePath = $this->arrayManager->findPath('tnw_subscr_schedule', $meta, 'subscription-options');
        if (!empty($schedulePath)) {
            $meta = $this->arrayManager->merge("$schedulePath/arguments/data/config", $meta, [
                'component' => 'TNW_Subscriptions/js/components/tnw-subscr-schedule',
                'componentType' => 'field',
            ]);
        }

        $recurringOptionsPath = $this->arrayManager
            ->findPath('container_recurring_options', $meta, 'subscription-options');

        if (!empty($recurringOptionsPath)) {
            $meta = $this->arrayManager->merge("$recurringOptionsPath/arguments/data/config", $meta, [
                'imports' => [
                    'visible' => '!ns = ${ $.ns }, index = tnw_subscr_schedule:selectDefinedByCustomer',
                ]
            ]);
        }

        $containerCommonPath = $this->arrayManager->findPath('container_common', $meta, $recurringOptionsPath);
        if (!empty($containerCommonPath)) {
            $meta = $this->arrayManager->remove(
                "$containerCommonPath/children/price/arguments/data/config/imports/disabled",
                $meta
            );

            $meta = $this->arrayManager->merge(
                "$containerCommonPath/children/price/arguments/data/config",
                $meta,
                ['disabled' => true]
            );

            $meta = $this->arrayManager->merge(
                "$containerCommonPath/children/initial_fee/arguments/data/config",
                $meta,
                ['disabled' => true]
            );
        }

        return $meta;
    }
}
