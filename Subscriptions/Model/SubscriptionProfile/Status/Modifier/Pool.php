<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status\Modifier;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Pool
 */
class Pool implements PoolInterface
{
    /**
     * @var array
     */
    protected $modifiers = [];

    /**
     * @var array
     */
    protected $modifiersInstances = [];

    /**
     * Object manager.
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Pool constructor.
     * @param ObjectManagerInterface $objectManager
     * @param array $modifiers
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $modifiers
    ) {
        $this->objectManager = $objectManager;
        $this->modifiers = $modifiers;
    }

    /**
     * {@inheritdoc}
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * {@inheritdoc}
     */
    public function getModifiersInstances()
    {
        if (!$this->modifiersInstances) {
            foreach ($this->modifiers as $modifier) {
                if (empty($modifier['class'])) {
                    throw new LocalizedException(__('Parameter "class" must be present.'));
                }

                if (empty($modifier['sortOrder'])) {
                    throw new LocalizedException(__('Parameter "sortOrder" must be present.'));
                }

                $modifierObject = $this->objectManager->create($modifier['class']);
                if (!$modifierObject instanceof ModifierInterface) {
                    throw new \InvalidArgumentException(
                        'Type "' . $modifier['class'] . '" is not instance on ' . ModifierInterface::class
                    );
                }
                $this->modifiersInstances[$modifier['class']] = $modifierObject;
            }
        }

        return $this->modifiersInstances;
    }
}