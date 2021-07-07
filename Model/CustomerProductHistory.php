<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use Magento\Framework\Model\AbstractModel;
use TNW\Subscriptions\Api\Data\CustomerProductHistoryInterface;
use TNW\Subscriptions\Model\ResourceModel\CustomerProductHistory as CustomerProductHistoryResourceModel;

/**
 * Class CustomerProductHistory - class used as Customer Product History model
 */
class CustomerProductHistory extends AbstractModel implements CustomerProductHistoryInterface
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(CustomerProductHistoryResourceModel::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfileId()
    {
        return (int)$this->getData(CustomerProductHistoryInterface::SUBSCRIPTION_PROFILE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setProfileId($profileId)
    {
        return $this->setData(CustomerProductHistoryInterface::SUBSCRIPTION_PROFILE_ID, $profileId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerId()
    {
        return (int)$this->getData(CustomerProductHistoryInterface::CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(CustomerProductHistoryInterface::CUSTOMER_ID, $customerId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMagentoProductId()
    {
        return (int)$this->getData(CustomerProductHistoryInterface::MAGENTO_PRODUCT_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setMagentoProductId($productId)
    {
        return $this->setData(CustomerProductHistoryInterface::MAGENTO_PRODUCT_ID, $productId);
    }
}
