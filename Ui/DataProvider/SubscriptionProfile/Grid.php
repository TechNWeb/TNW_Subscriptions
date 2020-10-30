<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Class Grid - SubscriptionProfile DataProvider
 */
class Grid extends DataProvider
{
    /**
     * {@inheritdoc}
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        $field = $filter->getField();

        if ($field === 'frequency_label') {
            $filter->setField('frequency.label');
        }

        if ($field === 'next_billing_cycle_date') {
            $filter->setField(new \Zend_Db_Expr('relation.scheduled_at'));
        }

        if ($field === 'label') {
            $filter->setValue(
                str_ireplace(
                    SubscriptionProfileInterface::LABEL_PREFIX,
                    '',
                    $filter->getValue()
                )
            );
            $filter->setField('main_table.entity_id');
        }

        if ($field === 'customer_email') {
            $filter->setField('customer.email');
        }

        if ($field === 'customer_id') {
            $filter->setField('customer.name');
        }

        if ($field === 'website_id') {
            $filter->setField('main_table.website_id');
        }

        if ($field === 'status') {
            $filter->setField('main_table.status');
        }

        if ($field === 'product_name') {
            $filter->setField('profile_product.name');
        }

        parent::addFilter($filter);
    }
}
