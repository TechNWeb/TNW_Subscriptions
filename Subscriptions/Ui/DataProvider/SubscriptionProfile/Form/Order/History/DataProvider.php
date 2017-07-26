<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Order\History;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use Magento\Framework\App\RequestInterface;

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
     * @inheritdoc
     */
    public function getCollection()
    {
        /** @var Collection $collection */
        $collection = parent::getCollection();
        $profileId = $this->request->getParam('subscription_profile_id', 0);

        if ($profileId){
            $collection->addFieldToFilter('subscription_profile_id' , $profileId);
        }

        $collection->join(
            SubscriptionProfileOrderInterface::MAIN_TABLE,
            $collection->getResource()->getIdFieldName() .'='. SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID
        );

        return $collection;
    }
}
