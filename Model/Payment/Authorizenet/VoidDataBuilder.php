<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Authorizenet;

/**
 * Class VoidDataBuilder - authorizenet
 */
class VoidDataBuilder extends \TNW\Subscriptions\Model\Payment\VoidDataBuilder
{
    /**
     * @param $data
     * @return mixed
     */
    public function build($data)
    {
        return [
            'transaction_request' => [
                'ref_trans_id' => $data['transaction_id']
            ],
            'store_id' => $data['store_id']
        ];
    }
}
