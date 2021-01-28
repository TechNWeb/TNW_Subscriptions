<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;

/**
 * Class ProductBillingFrequency - model
 */
class ProductBillingFrequency extends \Magento\Framework\Model\AbstractModel implements ProductBillingFrequencyInterface
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency::class);
    }

    /**
     * Get id
     * @return string
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Set id
     * @param string $id
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get billing_frequency_id
     * @return string
     */
    public function getBillingFrequencyId()
    {
        return $this->getData(self::BILLING_FREQUENCY_ID);
    }

    /**
     * Set billing_frequency_id
     * @param string $billing_frequency_id
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setBillingFrequencyId($billing_frequency_id)
    {
        return $this->setData(self::BILLING_FREQUENCY_ID, $billing_frequency_id);
    }

    /**
     * Get magento_product_id
     * @return string
     */
    public function getMagentoProductId()
    {
        return $this->getData(self::MAGENTO_PRODUCT_ID);
    }

    /**
     * Set magento_product_id
     * @param string $magento_product_id
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setMagentoProductId($magento_product_id)
    {
        return $this->setData(self::MAGENTO_PRODUCT_ID, $magento_product_id);
    }

    /**
     * Get default_billing_frequency
     * @return string
     */
    public function getDefaultBillingFrequency()
    {
        return $this->getData(self::DEFAULT_BILLING_FREQUENCY);
    }

    /**
     * Set default_billing_frequency
     * @param string $default_billing_frequency
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setDefaultBillingFrequency($default_billing_frequency)
    {
        return $this->setData(self::DEFAULT_BILLING_FREQUENCY, $default_billing_frequency);
    }

    /**
     * Get price
     * @return string
     */
    public function getPrice()
    {
        return $this->getData(self::PRICE);
    }

    /**
     * Set price
     * @param string $price
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setPrice($price)
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * Get initial_fee
     * @return string
     */
    public function getInitialFee()
    {
        return $this->getData(self::INITIAL_FEE);
    }

    /**
     * Set initial_fee
     * @param string $initial_fee
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setInitialFee($initial_fee)
    {
        return $this->setData(self::INITIAL_FEE, $initial_fee);
    }

    /**
     * {@inheritdoc}
     */
    public function getPresetQty()
    {
        return $this->getData(self::PRESET_QTY);
    }

    /**
     * {@inheritdoc}
     */
    public function setPresetQty($presetQty)
    {
        return $this->setData(self::PRESET_QTY, $presetQty);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsDisabled()
    {
        return $this->getData(self::IS_DISABLED);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsDisabled($isDisabled)
    {
        return $this->setData(self::IS_DISABLED, $isDisabled);
    }
}
