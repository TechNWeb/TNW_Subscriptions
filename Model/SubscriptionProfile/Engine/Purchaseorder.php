<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\OfflinePayments\Model\Purchaseorder as PurchaseorderPayment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Class Purchaseorder - used as purchase order payment processor
 */
class Purchaseorder extends Base
{
    /**
     * Field name for purchase order number
     */
    const PO_FIELD = 'po_number';

    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        $result = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
            : [];
        $result[OrderPaymentInterface::METHOD] =  PurchaseorderPayment::PAYMENT_METHOD_PURCHASEORDER_CODE;
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(\Magento\Quote\Model\Quote\Payment $payment)
    {
        return [
            'encoded_payment_additional_info' => [
                self::PO_FIELD => $payment->getPoNumber()
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function processProfileByRequestData($requestData)
    {
        $this->getProfile()->getPayment()->setEncodedPaymentAdditionalInfo([
            self::PO_FIELD => $requestData['payment']['purchaseorder']['additional'][self::PO_FIELD]
        ]);
        $this->getProfile()->getPayment()->setTokenHash('');
        return $this;
    }
}
