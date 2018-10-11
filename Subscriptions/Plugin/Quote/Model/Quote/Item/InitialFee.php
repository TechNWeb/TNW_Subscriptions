<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model\Quote\Item;

class InitialFee
{
    /**
     * @param \Magento\Quote\Model\Quote\Item $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterGetCalculationPriceOriginal(
        \Magento\Quote\Model\Quote\Item $subject,
        $result
    ) {
        $extensionAttributes = $subject->getExtensionAttributes();
        if (!$extensionAttributes instanceof \Magento\Quote\Api\Data\CartItemExtensionInterface) {
            return $result;
        }

        $subsInitialFees = $extensionAttributes->getSubsInitialFees();
        if (!$subsInitialFees instanceof \TNW\Subscriptions\Model\Sales\ExtensionAttributes\QuoteItem) {
            return $result;
        }

        return $result + $subsInitialFees->getSubsInitialFee();
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterGetBaseCalculationPriceOriginal(
        \Magento\Quote\Model\Quote\Item $subject,
        $result
    ) {
        $extensionAttributes = $subject->getExtensionAttributes();
        if (!$extensionAttributes instanceof \Magento\Quote\Api\Data\CartItemExtensionInterface) {
            return $result;
        }

        $subsInitialFees = $extensionAttributes->getSubsInitialFees();
        if (!$subsInitialFees instanceof \TNW\Subscriptions\Model\Sales\ExtensionAttributes\QuoteItem) {
            return $result;
        }

        return $result + $subsInitialFees->getBaseSubsInitialFee();
    }
}
