<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

/**
 * Interface ProductBillingFrequencyInterface - determines the product BF to be implemented functionality
 */
interface ProductBillingFrequencyInterface
{
    /**#@+
     * Constants for field names
     */
    const SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE = 'tnw_subscriptions_product_billing_frequency';
    const DEFAULT_BILLING_FREQUENCY = 'default_billing_frequency';
    const PRICE = 'price';
    const ID = 'id';
    const INITIAL_FEE = 'initial_fee';
    const BILLING_FREQUENCY_ID = 'billing_frequency_id';
    const MAGENTO_PRODUCT_ID = 'magento_product_id';
    const PRESET_QTY = 'preset_qty';
    const PRESET_QTY_SET_BY_MERCHANT = 'preset_qty_set_by_merchant';
    /**#@-*/

    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param $id
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     * @internal param string $id
     */
    public function setId($id);

    /**
     * Get billing_frequency_id
     * @return string|null
     */
    public function getBillingFrequencyId();

    /**
     * Set billing_frequency_id
     * @param string $billing_frequency_id
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setBillingFrequencyId($billing_frequency_id);

    /**
     * Get magento_product_id
     * @return string|null
     */
    public function getMagentoProductId();

    /**
     * Set magento_product_id
     * @param string $magento_product_id
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setMagentoProductId($magento_product_id);

    /**
     * Get default_billing_frequency
     * @return string|null
     */
    public function getDefaultBillingFrequency();

    /**
     * Set default_billing_frequency
     * @param string $default_billing_frequency
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setDefaultBillingFrequency($default_billing_frequency);

    /**
     * Get price
     * @return string|null
     */
    public function getPrice();

    /**
     * Set price
     * @param string $price
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setPrice($price);

    /**
     * Get initial_fee
     * @return string|null
     */
    public function getInitialFee();

    /**
     * Set initial_fee
     * @param string $initial_fee
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setInitialFee($initial_fee);

    /**
     * Get Preset Qty.
     *
     * @return mixed
     */
    public function getPresetQty();

    /**
     * Set Preset Qty.
     *
     * @param $presetQty
     * @return $this
     */
    public function setPresetQty($presetQty);
}
