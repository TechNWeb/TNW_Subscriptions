<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Quote;

class ItemGroup
{
    /**
     * @var \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator
     */
    private $descriptionCreator;

    public function __construct(
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator
    ) {
        $this->descriptionCreator = $descriptionCreator;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[]|null $items
     *
     * @return array
     */
    public function groups($items)
    {
        $group = [];
        foreach ((array)$items as $item) {
            $option = $item->getOptionByCode('subscription');
            if (null === $option) {
                $group['no_option'][] = $item;
            } else {
                $group[$option->getValue()][] = $item;
            }
        }

        uksort($group, function ($a, $b) {
            if ($b === 'no_option') {
                return 1;
            }

            return 0;
        });

        return $group;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $group
     *
     * @return \Magento\Framework\Phrase
     */
    public function caption($group)
    {
        static $quoteIndex = [];

        if (!$this->isSubscriptionGroup($group)) {
            return __('One-Time Purchase');
        }

        $key = spl_object_hash(reset($group));
        if (!isset($quoteIndex[$key])) {
            $quoteIndex[$key] = \count($quoteIndex) + 1;
        }

        return __('Subscription Profile #%1', $quoteIndex[$key]);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $group
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function frequencyDescription($group)
    {
        if (!$this->isSubscriptionGroup($group)) {
            $subtotal = array_reduce($group, function ($carry, \Magento\Quote\Model\Quote\Item $item) {
                $carry += $item->getRowTotal();
                return $carry;
            });

            return __('Subtotal: %1', $this->descriptionCreator->formatPrice($subtotal));
        }

        return $this->descriptionCreator->getDescriptionByGroup($group);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $group
     *
     * @return bool
     */
    public function isSubscriptionGroup($group)
    {
        return $this->isSubscriptionItem(reset($group));
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return bool
     */
    public function isSubscriptionItem($item)
    {
        return null !== $item->getOptionByCode('subscription');
    }
}