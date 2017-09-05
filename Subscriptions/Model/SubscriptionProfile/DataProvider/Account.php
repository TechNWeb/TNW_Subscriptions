<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Api\Filter;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\Pool;

class Account extends AbstractDataProvider
{
    /**#@+
     * Form request values
     */
    const FORM_DATA_KEY = 'account_form_data';
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

    /** Customers retrieving repository.
     *
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * Modifiers pool.
     *
     * @var Pool
     */
    private $modifiersPool;

    /**
     * Account constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param QuoteSessionInterface $session
     * @param CustomerRepository $customerRepository
     * @param PoolInterface $modifiersPool
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
        CustomerRepository $customerRepository,
        PoolInterface $modifiersPool,
        array $meta = [],
        array $data = []
    ) {
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->customerRepository = $customerRepository;
        $this->modifiersPool = $modifiersPool;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = [];
        $customerId = null;
        if ($this->session->getCustomerId()) {
            $customerId = $this->session->getCustomerId();
        }

        if ($customerId) {
            $dataModel = $this->customerRepository->getById($customerId);
            $data[static::FORM_DATA_VALUE] = [
                'account' => [
                    'group' => $dataModel->getGroupId(),
                    'email' => $dataModel->getEmail(),
                ],
            ];
        } else {
            $data[static::FORM_DATA_VALUE] = [
                'account' => [
                    'email' => $this->session->getCustomerEmail(),
                ],
            ];
        }

        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        return $data;
    }

    /**
     * @inheritdoc
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
