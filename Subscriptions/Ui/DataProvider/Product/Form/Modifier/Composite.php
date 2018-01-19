<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use TNW\Subscriptions\Model\Config;

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
     * Subscriptions config model.
     *
     * @var Config
     */
    private $config;

    /**
     * Composite constructor.
     * @param ObjectManagerInterface $objectManager
     * @param Config $config
     * @param array $modifiers
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Config $config,
        array $modifiers = []
    ) {
        $this->objectManager = $objectManager;
        $this->modifiers = $modifiers;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        if ($this->config->isSubscriptionsActive()) {
            $meta = $this->updateSubscriptionsTab($meta);
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
        } else {
            unset($meta['subscription-options']);
        }

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        if ($this->config->isSubscriptionsActive()) {
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
        }

        return $data;
    }

    /**
     * Updates meta for subscription tab.
     *
     * @param array $meta
     * @return array
     */
    private function updateSubscriptionsTab(array $meta)
    {
        $config = $meta['subscription-options']['arguments']['data']['config'];
        $config['label'] = __('Subscription Options');
        $config['additionalClasses'] = 'tnw-subscriptions-tab';
        $meta['subscription-options']['arguments']['data']['config'] = $config;

        return $meta;
    }
}
