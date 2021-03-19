<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Order\History;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;

/**
 * Class Order history data provider
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $arrItems = [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => [],
        ];
        $profileId = $this->request->getParam('subscription_profile_id', 0);
        if ($profileId) {
            $this->getCollection()->addFieldToFilter('relation.subscription_profile_id', $profileId);
            $this->getCollection()->getSelect()->join(
                ['relation' => $this->getCollection()->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)],
                'main_table.entity_id=relation.' . SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID,
                []
            );
            $arrItems['totalRecords'] = $this->getCollection()->getSize();
            foreach ($this->getCollection() as $item) {
                $arrItems['items'][] = $item->toArray([]);
            }
        }
        return $arrItems;
    }
}
