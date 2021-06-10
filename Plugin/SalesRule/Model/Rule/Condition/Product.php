<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\SalesRule\Model\Rule\Condition;

use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Model\Quote\ItemGroup;

/**
 * Class Product - plugin used to distinct subscription product rule action from legacy one
 */
class Product
{
    /**
     * @var ItemGroup
     */
    private $itemGroup;

    /**
     * Product constructor.
     * @param ItemGroup $itemGroup
     */
    public function __construct(
        ItemGroup $itemGroup
    ) {
        $this->itemGroup = $itemGroup;
    }

    /**
     * @param $subject
     * @param $result
     * @param AbstractModel $model
     * @return bool
     */
    public function afterValidate($subject, $result, AbstractModel $model)
    {
        if ($model instanceof Item) {
            $indexedGroups = array_filter($this->itemGroup->groups([$model]), function ($key) {
                return strcasecmp($key, 'no_option') !== 0;
            }, ARRAY_FILTER_USE_KEY);
            if (!$indexedGroups && $subject->getAttribute() == 'billing_cycle') {
                $result = false;
            } elseif ($model->getData('billing_cycle')) {
                $model->getProduct()->setBillingCycle($model->getData('billing_cycle'));
            } else {
                $model->getProduct()->setBillingCycle(0);
            }
        }
        return $result;
    }
}
