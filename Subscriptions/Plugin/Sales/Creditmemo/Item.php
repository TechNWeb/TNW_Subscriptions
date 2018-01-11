<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\CreditmemoItem as CreditmemoInitialFees;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\OrderItem as OrderInitialFees;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Credit memo item class plugin for updating subscription initial fee after register/cancel credit memo item.
 */
class Item
{
    /**
     * Updates refunded subscription initial fee to order item after register credit memo.
     *
     * @param CreditmemoItem $subject
     * @param CreditmemoItem $result
     * @return CreditmemoItem
     */
    public function afterRegister(
        CreditmemoItem $subject,
        CreditmemoItem $result
    ) {
        $orderItem = $result->getOrderItem();
        $orderInitialFees = $this->getOrderItemInitialFees($orderItem);
        $creditmemoInitialFees = $this->getCreditmemoItemInitialFees($result);

        if ($orderInitialFees && $creditmemoInitialFees) {
            $orderInitialFees->setSubsInitialFeeRefunded(
                $orderInitialFees->getSubsInitialFeeRefunded() + $creditmemoInitialFees->getSubsInitialFee()
            );
            $orderInitialFees->setBaseSubsInitialFeeRefunded(
                $orderInitialFees->getBaseSubsInitialFeeRefunded() + $creditmemoInitialFees->getBaseSubsInitialFee()
            );

            if ($orderItem->getExtensionAttributes()) {
                $orderItem->getExtensionAttributes()->setSubsInitialFees($orderInitialFees);
            }
        }

        return $result;
    }

    /**
     * Updates invoiced subscription initial fee to order item after cancel credit memo item.
     *
     * @param CreditmemoItem $subject
     * @param CreditmemoItem $result
     * @return CreditmemoItem
     */
    public function afterCancel(
        CreditmemoItem $subject,
        CreditmemoItem $result
    ) {
        $orderItem = $this->getOrderItem();
        $orderInitialFees = $this->getOrderItemInitialFees($orderItem);
        $creditmemoInitialFees = $this->getCreditmemoItemInitialFees($result);

        if ($orderInitialFees && $creditmemoInitialFees) {
            $orderInitialFees->setSubsInitialFeeRefunded(
                $orderInitialFees->getSubsInitialFeeRefunded() - $creditmemoInitialFees->getSubsInitialFee()
            );
            $orderInitialFees->setBaseSubsInitialFeeRefunded(
                $orderInitialFees->getBaseSubsInitialFeeRefunded() - $creditmemoInitialFees->getBaseSubsInitialFee()
            );

            if ($orderItem->getExtensionAttributes()) {
                $orderItem->getExtensionAttributes()->setSubsInitialFees($orderInitialFees);
            }
        }

        return $result;
    }

    /**
     * Returns order item initial fee extension attribute.
     *
     * @param OrderItem $orderItem
     * @return null|OrderInitialFees
     */
    private function getOrderItemInitialFees(OrderItem $orderItem)
    {
        return $orderItem->getExtensionAttributes()
            ? $orderItem->getExtensionAttributes()->getSubsInitialFees()
            : null;
    }

    /**
     * Returns credit memo item initial fee extension attribute.
     *
     * @param CreditmemoItem $creditmemoItem
     * @return null|CreditmemoInitialFees
     */
    private function getCreditmemoItemInitialFees(CreditmemoItem $creditmemoItem)
    {
        return $creditmemoItem->getExtensionAttributes()
            ? $creditmemoItem->getExtensionAttributes()->getSubsInitialFees()
            : null;
    }
}
