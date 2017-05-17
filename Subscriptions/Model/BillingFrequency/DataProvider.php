<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\BillingFrequency;

use Magento\Framework\App\Request\DataPersistorInterface;
use TNW\Subscriptions\Model\ResourceModel\BillingFrequency\CollectionFactory;
use TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Form\Modifier\LinkedProducts;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /** @var \TNW\Subscriptions\Model\ResourceModel\BillingFrequency\Collection */
    protected $collection;

    /** @var DataPersistorInterface */
    protected $dataPersistor;

    protected $loadedData;

    /** @var LinkedProducts */
    protected $linkedProductsModifier;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param LinkedProducts $linkedProductsModifier
     * @param array $meta
     * @param array $data
     * @internal param CollectionFactory $blockCollectionFactory
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        LinkedProducts $linkedProductsModifier,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->linkedProductsModifier = $linkedProductsModifier;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $model) {
            $this->loadedData[$model->getId()] = $model->getData();
        }
        $data = $this->dataPersistor->get('tnw_subscriptions_billingfrequency');

        if (!empty($data)) {
            $model = $this->collection->getNewEmptyItem();
            $model->setData($data);
            $this->loadedData[$model->getId()] = $model->getData();
            $this->dataPersistor->clear('tnw_subscriptions_billingfrequency');
        }

        $this->loadedData = $this->linkedProductsModifier->modifyData($this->loadedData);

        return $this->loadedData;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        $meta = $this->linkedProductsModifier->modifyMeta($meta);

        return $meta;
    }
}
