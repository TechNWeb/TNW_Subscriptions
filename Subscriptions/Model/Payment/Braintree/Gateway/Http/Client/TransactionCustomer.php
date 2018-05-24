<?php

namespace TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client;

use Magento\Braintree\Gateway\Http\Client\AbstractTransaction;

/**
 * Class TransactionSale
 * @property \TNW\Subscriptions\Model\Payment\Braintree\AdapterFactory $adapterFactory
 */
class TransactionCustomer extends AbstractTransaction
{
    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $storeId = $data['store_id'] ?? null;
        // sending store id and other additional keys are restricted by Braintree API
        unset($data['store_id']);

        return $this->adapterFactory->create($storeId)
            ->customer($data);
    }
}
