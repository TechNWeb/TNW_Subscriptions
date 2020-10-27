<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Sales\ExtensionAttributes;

use Magento\Framework\Model\AbstractModel;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;
use TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\QuoteItem as Resource;

/**
 * Class for quote item extension attribute.
 */
class QuoteItem extends AbstractModel implements SalesExtensionAttributesInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Resource::class);
    }

    /**
     * @inheritdoc
     */
    public function getItemId()
    {
        return $this->getData(static::MAGENTO_ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setItemId($itemId)
    {
        return $this->setData(self::MAGENTO_ITEM_ID, $itemId);
    }

    /**
     * @inheritdoc
     */
    public function getSubsInitialFee()
    {
        return $this->getData(static::EXT_ATTRIBUTE_INITIAL_FEE);
    }

    /**
     * @inheritdoc
     */
    public function setSubsInitialFee($initialFee)
    {
        return $this->setData(self::EXT_ATTRIBUTE_INITIAL_FEE, $initialFee);
    }

    /**
     * @inheritdoc
     */
    public function getBaseSubsInitialFee()
    {
        return $this->getData(static::EXT_ATTRIBUTE_BASE_INITIAL_FEE);
    }

    /**
     * @inheritdoc
     */
    public function setBaseSubsInitialFee($baseInitialFee)
    {
        return $this->setData(self::EXT_ATTRIBUTE_BASE_INITIAL_FEE, $baseInitialFee);
    }
}
