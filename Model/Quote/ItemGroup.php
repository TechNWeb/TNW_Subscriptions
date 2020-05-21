<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Quote;

use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator;
use TNW\Subscriptions\Service\Serializer;

class ItemGroup
{
    /**
     * @var DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var BillingFrequencyRepositoryInterface
     */
    private $billingFrequencyRepository;

    /**
     * ItemGroup constructor.
     * @param DescriptionCreator $descriptionCreator
     * @param BillingFrequencyRepositoryInterface $billingFrequencyRepository
     * @param Serializer $serializer
     */
    public function __construct(
        DescriptionCreator $descriptionCreator,
        BillingFrequencyRepositoryInterface $billingFrequencyRepository,
        Serializer $serializer
    ) {
        $this->descriptionCreator = $descriptionCreator;
        $this->serializer = $serializer;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
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
                $billingFrequency = $this->serializer->unserialize($option->getValue())['billing_frequency'];
                $group[$billingFrequency][] = $item;
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
     * @return \Magento\Framework\Phrase|string
     */
    public function caption($group, $frequency_id = null)
    {
        if (!$this->isSubscriptionGroup($group)) {
            return __('One-Time Purchase');
        }
        if (null !== $frequency_id) {
            return __('%1 Purchase', $this->billingFrequencyRepository->getById($frequency_id)->getLabel());
        }
        return '';
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
