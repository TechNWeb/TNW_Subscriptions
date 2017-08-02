<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options for popup "Customer account already exists".
 */
class CustomerExistsPopup implements OptionSourceInterface
{
    /**
     * Data Persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $customerName = $this->dataPersistor->get('existsCustomerName');

        $result =
            [
                [
                    'label' => __(
                        'Subscription should be linked to the existing account for <b>' . $customerName . '</b>'
                    ),
                    'value' => 'link'
                ],
                [
                    'label' => __('I will re-enter the email address'),
                    'value' => 'cancel'
                ]
            ];


        return $result;
    }
}
