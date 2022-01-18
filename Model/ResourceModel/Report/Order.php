<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\Report;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Timezone\Validator;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Reports\Model\FlagFactory;
use Magento\Reports\Model\ResourceModel\Report\AbstractReport;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Model\ResourceModel\Report\Subscriptions\CreatedAtFactory;
use TNW\Subscriptions\Model\ResourceModel\Report\Subscriptions\UpdatedAtFactory;

/**
 * Subscription Order entity resource model
 */
class Order extends AbstractReport
{
    /**
     * Subscriptions report aggregate table last refresh flag.
     */
    const REPORT_SUBSCRIPTION_FLAG_CODE = 'report_subscription_aggregated';

    /**
     * @var CreatedatFactory
     */
    protected $createdAtFactory;

    /**
     * @var UpdatedatFactory
     */
    protected $updatedAtFactory;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param TimezoneInterface $localeDate
     * @param FlagFactory $reportsFlagFactory
     * @param Validator $timezoneValidator
     * @param DateTime $dateTime
     * @param CreatedAtFactory $createDatFactory
     * @param UpdatedAtFactory $updateDatFactory
     * @param $connectionName
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        TimezoneInterface $localeDate,
        FlagFactory $reportsFlagFactory,
        Validator $timezoneValidator,
        DateTime $dateTime,
        CreatedAtFactory $createdAtFactory,
        UpdatedAtFactory $updatedAtFactory,
        $connectionName = null
    ) {
        parent::__construct(
            $context,
            $logger,
            $localeDate,
            $reportsFlagFactory,
            $timezoneValidator,
            $dateTime,
            $connectionName
        );
        $this->createdAtFactory = $createdAtFactory;
        $this->updatedAtFactory = $updatedAtFactory;
    }

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('tnw_subscriptions_sales_order_aggregated_created', 'id');
    }

    /**
     * Aggregate Orders data
     *
     * @param string|int|\DateTime|array|null $from
     * @param string|int|\DateTime|array|null $to
     * @return $this
     */
    public function aggregate($from = null, $to = null)
    {
        $this->createdAtFactory->create()->aggregate($from, $to);
        $this->updatedAtFactory->create()->aggregate($from, $to);
        $this->_setFlagData(self::REPORT_SUBSCRIPTION_FLAG_CODE);
        return $this;
    }
}
