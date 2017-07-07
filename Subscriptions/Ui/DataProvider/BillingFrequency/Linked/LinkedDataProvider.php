<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Linked;

use Magento\Catalog\Ui\DataProvider\Product\Related\AbstractDataProvider;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;

/**
 * Class LinkedDataProvider
 */
class LinkedDataProvider extends AbstractDataProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getLinkType()
    {
        return 'linked';
    }

    /**
     * @inheritdoc
     */
    public function getCollection()
    {
        $collection = parent::getCollection();
        $collection->addAttributeToFilter(
            'tnw_subscr_purchase_type',
            [
                'in' => [
                    PurchaseType::RECURRING_PURCHASE_TYPE,
                    PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE,
                ],
            ]
        );

        return $collection;
    }
}
