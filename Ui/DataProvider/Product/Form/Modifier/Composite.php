<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace  TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
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
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var array
     */
    private $supportTypes;

    /**
     * Composite constructor.
     * @param ObjectManagerInterface $objectManager
     * @param Config $config
     * @param LocatorInterface $locator
     * @param array $supportTypes
     * @param array $modifiers
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Config $config,
        LocatorInterface $locator,
        array $supportTypes,
        array $modifiers = []
    ) {
        $this->objectManager = $objectManager;
        $this->config = $config;
        $this->locator = $locator;
        $this->modifiers = $modifiers;
        $this->supportTypes = $supportTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        if ($this->config->isSubscriptionsActive() && $this->isSupportProductType()) {
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
        if ($this->config->isSubscriptionsActive() && $this->isSupportProductType()) {
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

    /**
     * @return bool
     */
    private function isSupportProductType()
    {
        $productType = \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE;
        $product = $this->locator->getProduct();

        if ($product) {
            $productType = $product->getTypeId();
        }

        return in_array($productType, $this->supportTypes);
    }
}
