<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use Magento\Framework\Api\Filter;


class CustomerExistsForm extends AbstractDataProvider
{
    /**#@+
     * Form request values
     */
    const FORM_DATA_KEY = 'add_product_modal_configurable_form_data';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    /**
     * Form data scope.
     */
    const DATA_SCOPE_CUSTOMER_ALREADY_EXISTS_MODAL_FORM = 'tnw_subscriptionprofile_customer_exists_modal_form';


    protected $scopeName;

    /** @var [] */
    protected $loadedData;

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var StepPool */
    protected $stepPool;

    /** @var Registry */
    protected $registry;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param Registry $registry
     * @param string $scope
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlInterface $urlBuilder,
        StepPool $stepPool,
        Registry $registry,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->registry = $registry;
        $this->scopeName = $scope ? $scope : self::DATA_SCOPE_CUSTOMER_ALREADY_EXISTS_MODAL_FORM . '.'
            . self::DATA_SCOPE_CUSTOMER_ALREADY_EXISTS_MODAL_FORM;
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

        //$data[self::FORM_DATA_VALUE]['qty'] = self::DEFAULT_QTY_VALUE;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {

    }
}
