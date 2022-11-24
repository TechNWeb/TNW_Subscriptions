<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Model\Config as SubscriptionConfig;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier\PaymentModifierInterface;

/**
 * Payment step form data provider.
 */
class Payment extends AbstractDataProvider
{
    /**#@+
     * Form data scope
     */
    const DATA_SCOPE_PAYMENT_FORM = 'tnw_subscriptionprofile_create_payment_form';
    /**#@-*/

    /**#@+
     * Form request values
     */
    const PAYMENT_FORM_DATA_KEY = 'payment_form_data';
    const PAYMENT_FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Modifiers pool.
     *
     * @var PoolInterface
     */
    protected $modifiersPool;

    /**
     * @var SubscriptionConfig
     */
    protected $subscriptionConfig;

    /**
     * Payment constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param PoolInterface $modifiersPool
     * @param SubscriptionConfig $subscriptionConfig
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlInterface $urlBuilder,
        PoolInterface $modifiersPool,
        SubscriptionConfig $subscriptionConfig,
        array $meta = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->modifiersPool = $modifiersPool;
        $this->subscriptionConfig = $subscriptionConfig;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConfigData()
    {
        $configData = parent::getConfigData();

        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $configData = $modifier->modifyConfigData($configData);
        }

        $configData['submit_url'] = $this->urlBuilder->getUrl(
            'tnw_subscriptions/subscriptionprofile_create/process'
        );
        $configData['process_url'] = $this->urlBuilder->getUrl(
            'tnw_subscriptions/subscriptionprofile_create/process'
        );
        $configData['static_auth_amount'] = $this->subscriptionConfig->getStaticAuthAmount();

        return $configData;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
        return $this;
    }

    /**
     * @inheritdoc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            if ($modifier instanceof PaymentModifierInterface) {
                $modifier->setPaymentFormName($this::DATA_SCOPE_PAYMENT_FORM);
            }
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }
}
