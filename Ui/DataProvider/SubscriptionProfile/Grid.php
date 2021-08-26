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

        if ($field === 'entity_id') {
            $filter->setField('main_table.entity_id');
        }

        if ($field === 'frequency_label') {
            $filter->setField('frequency.label');
        }

        if ($field === 'billing_frequency_id') {
            $filter->setField('main_table.billing_frequency_id');
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

        if ($field === 'customer_id' && !is_numeric($filter->getValue())) {
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

        if ($field === 'product_id') {
            $filter->setField('profile_product.magento_product_id');
        }

        if ($field === 'child_sku') {
            $filter->setField(new \Zend_Db_Expr("json_extract(profile_product.custom_options, '$.simple_sku')"));
        }

        parent::addFilter($filter);
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareUpdateUrl()
    {
        if (!isset($this->data['config']['filter_url_params'])) {
            return;
        }
        foreach ($this->data['config']['filter_url_params'] as $paramName => $paramValue) {
            if ('*' == $paramValue) {
                $paramValue = $this->request->getParam($paramName);
            }
            if ($paramValue) {
                $this->data['config']['update_url'] = sprintf(
                    '%s%s/%s/',
                    $this->data['config']['update_url'],
                    $paramName,
                    $paramValue
                );
                if ($paramName === 'status') {
                    $filter = $this->filterBuilder->setField($paramName)
                        ->setValue(explode(',', $paramValue))->setConditionType('in')->create();
                } else {
                    $filter = $this->filterBuilder->setField($paramName)
                        ->setValue($paramValue)->setConditionType('eq')->create();
                }
                $this->addFilter($filter);
            }
        }
    }
}
