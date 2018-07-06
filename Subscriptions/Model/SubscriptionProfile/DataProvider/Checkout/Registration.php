<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout;

use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\Api\Filter;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Data provider for registration step.
 */
class Registration extends AbstractDataProvider
{
    /**#@+
     * Form data scope
     */
    const DATA_SCOPE_REGISTRATION_FORM = 'tnw_subscriptionprofile_checkout_registration_form';
    /**#@-*/

    /**
     * Customer url.
     *
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * Url builder.
     *
     * @var UrlInterface
     */
    private $url;

    /**
     * Store manager.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Registration constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CustomerUrl $customerUrl
     * @param UrlInterface $url
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CustomerUrl $customerUrl,
        UrlInterface $url,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->customerUrl = $customerUrl;
        $this->url = $url;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {

    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        $meta = array_merge_recursive(
            $meta,
            [
                'registration' => [
                    'children' => [
                        'new_customer' => [
                            'arguments' => [
                                'data' => [
                                    'config' => $this->getNewCustomerConfig(),
                                ],
                            ],
                        ],
                        'existing_customer' => [
                            'arguments' => [
                                'data' => [
                                    'config' => $this->getExistCustomerConfig(),
                                ],
                            ],
                        ],
                    ]
                ]
            ]
        );

        return $meta;
    }

    /**
     * Returns additional config for new customer container.
     */
    private function getNewCustomerConfig()
    {
        return [
            'urlAddEmailToSession' => $this->url->getUrl('tnw_subscriptions/session/AddEmailToSession'),
        ];
    }

    /**
     * Returns additional config for exist customer container.
     */
    private function getExistCustomerConfig()
    {
        return [
            'forgotPasswordUrl' => $this->customerUrl->getForgotPasswordUrl(),
            'nextStepUrl' =>$this->url->getUrl('tnw_subscriptions/cart/index/') . '#address',
            'registerUrl' => $this->customerUrl->getRegisterUrl(),
            'baseUrl' =>  $this->storeManager->getStore()->getBaseUrl()
        ];
    }
}
