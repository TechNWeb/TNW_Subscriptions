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
use TNW\Subscriptions\Model\Backend\Session\Quote;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\Pool;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\CustomerExistsForm;

class Account extends AbstractDataProvider
{
    /**#@+
     * Form request values
     */
    const FORM_DATA_KEY = 'account_form_data';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    const CUSTOMER_EXISTS_FORM_HANDLER = 'tnw_subscriptions_subscriptionprofile_customer_exists';

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
     * @var Quote
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
     * @param Quote $session
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
        Quote $session,
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

        $meta = array_merge_recursive(
            $meta,
            $this->getMetaData()
        );

        return $meta;
    }

    /**
     * @return array
     */
    private function getMetaData()
    {
        $result = [
            'customer_account_already_exists' => [
                'children' => [
                    'customerModal' => $this->getCustomerExistModal(),
                ],
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => '',
                            'collapsible' => false,
                            'componentType' => Fieldset::NAME,
                            'dataScope' => '',
                        ],
                    ],
                ]
            ]
        ];


        return $result;
    }

    /**
     * @return array
     */
    private function getCustomerExistModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'options' => [
                            'modalClass' => 'modal-popup',
                        ],
                    ],
                ],
            ],
            'children' => [
                'customer_account_already_exists' => $this->getCustomerExistForm()
            ]
        ];
    }

    /**
     * @return array
     */
    private function getCustomerExistForm()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => true,
                        'componentType' => Container::NAME,
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => self::CUSTOMER_EXISTS_FORM_HANDLER
                            ]
                        ),
                        'autoRender' => true,
                        'ns' => CustomerExistsForm::DATA_SCOPE_CUSTOMER_ALREADY_EXISTS_MODAL_FORM,
                        'externalProvider' => CustomerExistsForm::DATA_SCOPE_CUSTOMER_ALREADY_EXISTS_MODAL_FORM
                            . '.' . CustomerExistsForm::DATA_SCOPE_CUSTOMER_ALREADY_EXISTS_MODAL_FORM
                            . '_data_source',
                    ],
                ],
            ]
        ];
    }
}
