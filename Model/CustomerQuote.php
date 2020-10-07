<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\Data\CustomerQuoteInterface;
use Magento\Framework\Model\AbstractModel;
use TNW\Subscriptions\Model\ResourceModel\CustomerQuote as Resource;

/**
 * Class CustomerQuote - class used as customer Quote model
 */
class CustomerQuote extends AbstractModel implements CustomerQuoteInterface
{
    /**#@+
     * Main table name.
     */
    const CUSTOMER_QUOTE_TABLE = 'tnw_subscriptions_customer_quote';
    /**#@-*/

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Resource::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuoteId()
    {
        return $this->getData(static::QUOTE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerId()
    {
        return $this->getData(static::CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }
}
