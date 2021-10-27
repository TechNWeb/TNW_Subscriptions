<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Model\Order\Email\Sender;

use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

/**
 * Class PreventSendInvoiceEmail preventing sending invoice
 * email if it already sent.
 */
class PreventSendInvoiceEmail
{
    /**
     * @param InvoiceSender $invoiceSender
     * @param $proceed
     * @param $invoice
     * @return bool|mixed
     */
    public function aroundSend(InvoiceSender $invoiceSender, $proceed, $invoice)
    {
        if (array_key_exists('send_email', $invoice->getData())
            && $invoice->getData()['send_email'] === true
        ) {
            $result = true;
        } else {
            $result = $proceed($invoice);
        }
        return $result;
    }
}
