<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout;

use Magento\Framework\Api\Filter;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Model\Backend\UrlBuilder;

/**
 * Class ThankYouPage data provider.
 */
class ThankYouPage extends AbstractDataProvider
{
    /**
     * Form data scope
     */
    const DATA_SCOPE_THANKYOUPAGE_FORM = 'tnw_subscriptionprofile_checkout_thankyoupage_form';

    /**
     * Subscription url builder.
     *
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @param UrlBuilder $urlBuilder
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        UrlBuilder $urlBuilder,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
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
    public function getMeta()
    {
        return parent::getMeta();
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
    }
}
