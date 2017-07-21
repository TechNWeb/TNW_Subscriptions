<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\ObjectManagerInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;
use TNW\Subscriptions\Model\Backend\Session\Quote;

class Account extends AbstractDataProvider
{
    /**#@+
     * Form request values
     */
    const FORM_DATA_KEY = 'account_form_data';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    const ADDRESS_MODIFIER = 'TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Modifier\AddressModifier';
    
    /** @var UrlInterface */
    protected $urlBuilder;
    /** @var StepPool */
    protected $stepPool;
    /** @var Quote */
    private $session;
    /** @var CustomerRepository */
    private $customerRepository;
    /** @var \TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Modifier\Pool */
    private $modifiersPool;
    /** @var ObjectManagerInterface */
    private $objectManager;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param Quote $session
     * @param CustomerRepository $customerRepository
     * PoolInterface $modifiersPool
     * ObjectManagerInterface $objectManager
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
        ObjectManagerInterface $objectManager,
        array $meta = [],
        array $data = []
    ) {
        $this->session = $session;
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->customerRepository = $customerRepository;
        $this->modifiersPool = $modifiersPool;
        $this->objectManager = $objectManager;
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

        $data = $this->modifyPool($data, 'modifyData');

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

        $meta = $this->modifyPool($meta, 'modifyMeta');

        return $meta;
    }

    /**
     * Calls UIComponent modifiers.
     *
     * @param array $modificationData
     * @param string $method
     * @return array
     */
    private function modifyPool($modificationData, $method)
    {
        foreach ($this->modifiersPool->getModifiers() as $modifierData) {
            $modifierClass = $modifierData['class'];
            $modifier = $this->getModifierInstance($modifierClass);
            if ($modifierClass == self::ADDRESS_MODIFIER) {
                $modifier->setIsShippingFieldSet(true);
            }
            $modificationData = $modifier->$method($modificationData);
        }

        return $modificationData;

    }

    /**
     * @param string $modifierClass
     * @return ModifierInterface
     */
    private function getModifierInstance($modifierClass)
    {
        /** @var ModifierInterface $modifierClass */
        $modifier = $this->objectManager->get($modifierClass);
        if (!$modifier instanceof ModifierInterface) {
            throw new \InvalidArgumentException(
                'Type "' . $modifierClass . '" is not an instance of ' . ModifierInterface::class
            );
        }

        return $modifier;
    }
}
