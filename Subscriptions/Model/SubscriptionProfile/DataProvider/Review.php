<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Review\Form\Modifier\Pool;

class Review extends AbstractDataProvider
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var StepPool
     */
    private $stepPool;

    /**
     * Modifiers pool.
     *
     * @var Pool
     */
    private $modifiersPool;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlInterface $urlBuilder,
        StepPool $stepPool,
        PoolInterface $modifiersPool,
        array $meta = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->modifiersPool = $modifiersPool;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta,
            $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $data = [];
        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        return $data;
    }

    /**
     * @return array|mixed
     */
    public function getConfigData()
    {
        $configData = parent::getConfigData();

        $configData['submit_url'] = $this->urlBuilder->getUrl(
            '*/subscriptionprofile/save'
        );

        return $configData;
    }


    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }
}
