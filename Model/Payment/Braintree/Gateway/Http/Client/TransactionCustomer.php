<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Exception;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Model\Payment\Braintree\Adapter;

/**
 * Class TransactionCustomer - braintree
 */
class TransactionCustomer implements ClientInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Logger
     */
    protected $customLogger;

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * TransactionCustomer constructor.
     * @param LoggerInterface $logger
     * @param Logger $customLogger
     * @param Adapter $adapter
     */
    public function __construct(
        LoggerInterface $logger,
        Logger $customLogger,
        Adapter $adapter
    ) {
        $this->adapter = $adapter;
        $this->logger = $logger;
        $this->customLogger = $customLogger;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $data = $transferObject->getBody();
        $log = [
            'request' => $data,
            'client' => static::class
        ];
        $response['object'] = [];

        try {
            $response['object'] = $this->process($data);
        } catch (Exception $e) {
            $message = __($e->getMessage() ?: 'Sorry, but something went wrong');
            $this->logger->critical($message);
            throw new ClientException($message);
        } finally {
            $log['response'] = (array) $response['object'];
            $this->customLogger->debug($log);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $storeId = $data['store_id'] ?? null;
        // sending store id and other additional keys are restricted by Braintree API
        unset($data['store_id']);

        if (property_exists($this, 'adapter')) {
            return $this->adapter->customer($data);
        }

        return $this->adapterFactory->create($storeId)
            ->customer($data);
    }
}
