<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\QuoteSessionInterface;

class Store extends AbstractDataProvider
{
    /**#@+
     * Form request values
     */
    const FORM_DATA_KEY = 'store_form_data';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    /**
     * Url Builder.
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Steps pool for creating subscription.
     *
     * @var StepPool
     */
    private $stepPool;

    /**
     * Admin session.
     *
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * Store manager.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param QuoteSessionInterface $session
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlInterface $urlBuilder,
        StepPool $stepPool,
        QuoteSessionInterface $session,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->session = $session;
        $this->storeManager = $storeManager;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $data = [];

        $data[self::FORM_DATA_VALUE]['store_id'] = $this->getDefaultStoreId();
        if ($this->session->getStoreId()) {
            $data[self::FORM_DATA_VALUE]['store_id'] = $this->session->getStoreId();
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
            '*/subscriptionprofile_create/process',
            [
                StepPool::STEP_PARAM_NAME => $this->stepPool->getNextStep()
            ]
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
     * Get default store id
     *
     * @return int
     */
    private function getDefaultStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
