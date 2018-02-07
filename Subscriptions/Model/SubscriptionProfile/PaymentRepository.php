<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Api\PaymentRepositoryInterface;



use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterfaceFactory;
use TNW\Subscriptions\Api\Data\BillingFrequencySearchResultsInterfaceFactory;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\ResourceModel\BillingFrequency as ResourceBillingFrequency;
use TNW\Subscriptions\Model\ResourceModel\BillingFrequency\CollectionFactory as BillingFrequencyCollectionFactory;

class PaymentRepository implements PaymentRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function save(
        \TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface $subscriptionPayment
    )
    {
        $subscriptionPayment;
        // TODO: Implement save() method.
    }

    /**
     * @inheritDoc
     */
    public function getById($id)
    {
        $id;
        // TODO: Implement getById() method.
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    )
    {
        $searchCriteria;
        // TODO: Implement getList() method.
    }

    /**
     * @inheritDoc
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface $subscriptionPayment
    )
    {
        $subscriptionPayment;
        // TODO: Implement delete() method.
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id)
    {
        $id;
        // TODO: Implement deleteById() method.
    }
}
