<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Customer\Model\ResourceModel\CustomerRepository;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;

class Account extends AbstractDataProvider
{
    /**#@+
     * Form request values
     */
    const FORM_DATA_KEY = 'account_form_data';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    /** @var UrlInterface */
    protected $urlBuilder;
    /** @var StepPool */
    protected $stepPool;
    /** @var \TNW\Subscriptions\Model\Backend\Session\Quote */
    private $session;
    /** @var CustomerRepository */
    private $customerRepository;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param \TNW\Subscriptions\Model\Backend\Session\Quote $session
     * @param CustomerRepository $customerRepository
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlInterface $urlBuilder,
        StepPool $stepPool,
        \TNW\Subscriptions\Model\Backend\Session\Quote $session,
        CustomerRepository $customerRepository,
        array $meta = [],
        array $data = []
    ) {
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->customerRepository = $customerRepository;
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
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

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
                    'customerModal' => $this->getConfigurableModal(),
                ],
                'arguments' => [
                    'data' => [
                        'config' => [
                            'additionalClasses' => 'admin__fieldset-section customer-account-exists',
                            'label' => false,
                            'collapsible' => false,
                            'componentType' => Fieldset::NAME,
                            'dataScope' => '',
                            'style' => 'max-width: 100%'
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
    private function getConfigurableModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'componentType' => Modal::NAME,
                        'options' => [
                            'title' => __('Customer account already exists'),
                            'modalClass' => 'modal-popup',
                        ]
                    ],
                ],
            ],
            'children' => [
                //'customer_form' => $this->getConfigurableForm()
            ]
        ];
    }

    /**
     * @return array
     */
    private function getConfigurableForm()
    {
        //todo
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => true,
                        'label' => '',
                        'componentType' => Container::NAME,
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'dataScope' => '',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => '',
                                'buttons' => 1,
                                //ConfigurableForm::FORM_DATA_KEY => ConfigurableForm::FORM_DATA_VALUE
                            ]
                        ),
                        'autoRender' => false,
                        //'ns' => '' . ConfigurableForm::DATA_SCOPE_CONFIGURABLE_MODAL_FORM,
                        //'externalProvider' => ConfigurableForm::DATA_SCOPE_CONFIGURABLE_MODAL_FORM . '.' .
    //ConfigurableForm::DATA_SCOPE_CONFIGURABLE_MODAL_FORM . '_data_source',
                        'toolbarContainer' => '${ $.parentName }'
                    ],
                ],
            ]
        ];
    }
}
