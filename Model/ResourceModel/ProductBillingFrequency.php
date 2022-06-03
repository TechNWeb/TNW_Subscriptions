<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel;

use Magento\Framework\Serialize\SerializerInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Psr\Log\LoggerInterface;

/**
 * Class ProductBillingFrequency - ResourceModel
 */
class ProductBillingFrequency extends AbstractDb
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     *
     * ProductBillingFrequency constructor.
     * @param Context $context
     * @param SerializerInterface $serializer
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->logger = $logger;
        $this->serializer = $serializer;
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE,
            'id'
        );
    }

    /**
     * Checks if there is a record with current billing frequency id
     *
     * @param $id
     * @return bool
     */
    public function isBillingFrequencyAllowedToProduct($id)
    {
        $connection = $this->getConnection();
        $sql = $connection->select()
            ->from(
                ['main' => $this->getTable(
                    ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE
                )
                ],
                [ProductBillingFrequencyInterface::ID]
            )->where('main.' . ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID . '=?', $id);

        return count($this->getConnection()->fetchCol($sql)) > 0;
    }

    /**
     * Insert data in billing frequency table from product attribute
     *
     * @param $billingFrequency
     */
    public function setImportedBillingFrequency($billingFrequency, $productId = null)
    {
        $connection = $this->getConnection();
        try {
            $convertedData = $this->serializer->unserialize($billingFrequency);
            if (!empty($convertedData)) {
                $table = $this->getTable(
                    ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE
                );
                foreach ($convertedData as $value) {
                    if ($productId !== null) {
                        $value['magento_product_id'] = $productId;
                    }
                    $connection->insertOnDuplicate($table, $value, [
                        'billing_frequency_id',
                        'magento_product_id',
                        'default_billing_frequency',
                        'price',
                        'initial_fee',
                        'sort_order',
                        'preset_qty',
                        'is_disabled',
                        ]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
