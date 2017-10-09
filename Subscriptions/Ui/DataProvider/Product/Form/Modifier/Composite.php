<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

/**
 * Data provider for "Subscription Options" tab
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Composite extends AbstractModifier
{
    /**
     * @var array
     */
    protected $modifiers = [];

    /**
     * Object Manager
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $modifiers
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $modifiers = []
    ) {
        $this->objectManager = $objectManager;
        $this->modifiers = $modifiers;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        foreach ($this->modifiers as $bundleClass) {
            /** @var ModifierInterface $bundleModifier */
            $bundleModifier = $this->objectManager->get($bundleClass);
            if (!$bundleModifier instanceof ModifierInterface) {
                throw new \InvalidArgumentException(
                    'Type "' . $bundleClass . '" is not an instance of ' . ModifierInterface::class
                );
            }
            $meta = $bundleModifier->modifyMeta($meta);
        }

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        foreach ($this->modifiers as $bundleClass) {
            /** @var ModifierInterface $bundleModifier */
            $bundleModifier = $this->objectManager->get($bundleClass);
            if (!$bundleModifier instanceof ModifierInterface) {
                throw new \InvalidArgumentException(
                    'Type "' . $bundleClass . '" is not an instance of ' . ModifierInterface::class
                );
            }
            $data = $bundleModifier->modifyData($data);
        }

        return $data;
    }
}
