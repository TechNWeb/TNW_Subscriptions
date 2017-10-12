<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;

/**
 * Data provider for cancel button form in popup.
 */
class CancelButtonPopup extends AbstractDataProvider
{
    /**
     * Url Builder.
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Form data scope.
     */
    const DATA_SCOPE_CANCEL_BUTTON_MODAL_FORM = 'tnw_subscriptionprofile_cancel_button_popup_form';

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlInterface $urlBuilder,
        array $meta = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * {@inheritdoc}
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
    public function getConfigData()
    {
        $configData = parent::getConfigData();

        $configData['submit_url'] = $this->urlBuilder->getUrl(
            'tnw_subscriptions/subscriptionprofile/cancelsubscription'
        );

        return $configData;
    }
}
