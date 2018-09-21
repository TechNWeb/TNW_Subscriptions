<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Sales\ExtensionAttributes;

use Magento\Framework\Model\AbstractModel;
use TNW\Subscriptions\Api\Data\OrderItemExtensionAttributesInterface;
use TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\OrderItem as Resource;

/**
 * Class for order item extension attribute.
 */
class OrderItem extends AbstractModel implements OrderItemExtensionAttributesInterface
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

    /**
     * @inheritdoc
     */
    public function getBaseSubsInitialFeeInvoiced()
    {
        return $this->getData(static::EXT_ATTRIBUTE_BASE_INITIAL_FEE_INVOICED);
    }

    /**
     * @inheritdoc
     */
    public function setBaseSubsInitialFeeInvoiced($baseInitialFeeInvoiced)
    {
        return $this->setData(self::EXT_ATTRIBUTE_BASE_INITIAL_FEE_INVOICED, $baseInitialFeeInvoiced);
    }

    /**
     * @inheritdoc
     */
    public function getSubsInitialFeeInvoiced()
    {
        return $this->getData(static::EXT_ATTRIBUTE_INITIAL_FEE_INVOICED);
    }

    /**
     * @inheritdoc
     */
    public function setSubsInitialFeeInvoiced($initialFeeInvoiced)
    {
        return $this->setData(self::EXT_ATTRIBUTE_INITIAL_FEE_INVOICED, $initialFeeInvoiced);
    }

    /**
     * @inheritdoc
     */
    public function getSubsInitialFeeRefunded()
    {
        return $this->getData(static::EXT_ATTRIBUTE_INITIAL_FEE_REFUNDED);
    }

    /**
     * @inheritdoc
     */
    public function setSubsInitialFeeRefunded($initialFeeRefunded)
    {
        return $this->setData(self::EXT_ATTRIBUTE_INITIAL_FEE_REFUNDED, $initialFeeRefunded);
    }

    /**
     * @inheritdoc
     */
    public function getBaseSubsInitialFeeRefunded()
    {
        return $this->getData(static::EXT_ATTRIBUTE_BASE_INITIAL_FEE_REFUNDED);
    }

    /**
     * @inheritdoc
     */
    public function setBaseSubsInitialFeeRefunded($baseInitialFeeRefunded)
    {
        return $this->setData(self::EXT_ATTRIBUTE_BASE_INITIAL_FEE_REFUNDED, $baseInitialFeeRefunded);
    }
}
