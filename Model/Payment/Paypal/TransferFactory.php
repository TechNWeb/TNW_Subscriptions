<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

use Magento\Framework\DataObject;

/**
 * Class TransferFactory - pyapal factory
 */
class TransferFactory
{
    /**
     * @param $data
     * @return array
     */
    public function create($data)
    {
        if (!isset($data['transaction_id'])) {
            $result = [
                'request' => new DataObject($data['requestData']),
                'config' => $data['config']
            ];
        } else {
            $requestData = $data['paymentTransactionData']['requestData'];
            $requestData['trxtype'] = \Magento\Paypal\Model\Payflowpro::TRXTYPE_DELAYED_VOID;
            $requestData['origid'] = $data['transaction_id'];
            $result = [
                'request' => new DataObject($data['paymentTransactionData']['requestData']),
                'config' => $data['paymentTransactionData']['config']
            ];
        }
        return $result;
    }
}
