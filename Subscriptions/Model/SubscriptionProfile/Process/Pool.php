<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Process;

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
    protected $processors = [];

    /**
     * @var array
     */
    protected $processorsInstances = [];

    /**
     * Object manager.
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Pool constructor.
     * @param ObjectManagerInterface $objectManager
     * @param array $processors
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $processors = []
    ) {
        //@toDo $processors cant' be empty
        $this->objectManager = $objectManager;
        $this->processors = $processors;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessorsInstances()
    {
        if (!$this->processorsInstances) {
            foreach ($this->processors as $modifier) {
                if (empty($modifier['class'])) {
                    throw new LocalizedException(__('Parameter "class" must be present.'));
                }

                if (empty($modifier['sortOrder'])) {
                    throw new LocalizedException(__('Parameter "sortOrder" must be present.'));
                }

                $modifierObject = $this->objectManager->create($modifier['class']);
                if (!$modifierObject instanceof ProcessInterface) {
                    throw new \InvalidArgumentException(
                        'Type "' . $modifier['class'] . '" is not instance on ' . ProcessInterface::class
                    );
                }
                $this->processorsInstances[$modifier['class']] = $modifierObject;
            }
        }

        return $this->processorsInstances;
    }
}