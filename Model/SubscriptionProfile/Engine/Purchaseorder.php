<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\OfflinePayments\Model\Purchaseorder as PurchaseorderPayment;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class Purchaseorder
 */
class Purchaseorder extends Base
{
    /**
     *
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
