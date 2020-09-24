<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\Sales\Order\Items\Price;

use Magento\Weee\Block\Adminhtml\Items\Price\Renderer as WeeRenderer;
use Magento\Sales\Model\Order\CreditMemo\Item as CreditMemoItem;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Class Renderer
 */
class Renderer extends WeeRenderer
{
    /**
     * Return the total amount minus discount
     *
     * @param OrderItem|InvoiceItem|CreditMemoItem $item
     * @return mixed
     */
    public function getTotalAmount($item)
    {
        $totalAmount = parent::getTotalAmount($item);

        return $totalAmount + $this->getItemInitialFee($item);
    }

    /**
     * Return the total amount minus discount
     *
     * @param OrderItem|InvoiceItem|CreditMemoItem $item
     * @return mixed
     */
    public function getBaseTotalAmount($item)
    {
        $totalAmount = parent::getBaseTotalAmount($item);

        return $totalAmount + $this->getItemBaseInitialFee($item);
    }

    /**
     * @param OrderItem|InvoiceItem|CreditMemoItem $item
     * @return int
     */
    public function getItemInitialFee($item)
    {
        $initialFee = 0;
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees) {
            $initialFee = $initialFees->getSubsInitialFee();
        }

        return $initialFee;
    }

    /**
     * @param OrderItem|InvoiceItem|CreditMemoItem $item
     * @return int
     */
    public function getItemBaseInitialFee($item)
    {
        $baseInitialFee = 0;
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees) {
            $baseInitialFee = $initialFees->getBaseSubsInitialFee();
        }

        return $baseInitialFee;
    }
}
