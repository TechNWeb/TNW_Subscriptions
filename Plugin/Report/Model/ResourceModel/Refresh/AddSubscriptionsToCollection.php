<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Report\Model\ResourceModel\Refresh;

use Magento\Framework\DataObject;
use Magento\Reports\Model\FlagFactory;
use Magento\Reports\Model\ResourceModel\Refresh\Collection;
use TNW\Subscriptions\Model\ResourceModel\Report\Order;

/**
 * Adds Subscriptions to report statistics refresh list.
 */
class AddSubscriptionsToCollection
{
    /**
     * @var FlagFactory
     */
    protected $reportsFlagFactory;

    /**
     * @var bool
     */
    private $isDataAdded = false;

    /**
     * @param FlagFactory $flagFactory
     */
    public function __construct(FlagFactory $flagFactory)
    {
        $this->reportsFlagFactory = $flagFactory;
    }

    /**
     * @param Collection $collection
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterLoadData(Collection $collection, $result)
    {
        if (!$this->isDataAdded) {
            $item = new DataObject([
                'id' => 'subscriptions',
                'report' => __('Subscriptions'),
                'comment' => __('Subscriptions Report'),
                'updated_at' => $this->getUpdatedAt(Order::REPORT_SUBSCRIPTION_FLAG_CODE)
            ]);

            $collection->addItem($item);

            $this->isDataAdded = true;
        }

        return $result;
    }

    /**
     * @param $reportCode
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getUpdatedAt($reportCode)
    {
        $flag = $this->reportsFlagFactory->create()->setReportFlagCode($reportCode)->loadSelf();
        return $flag->hasData() ? $flag->getLastUpdate() : '';
    }
}
