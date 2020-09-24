<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

/**
 * Interface for subscription order item extension attributes.
 */
interface OrderItemExtensionAttributesInterface extends SalesExtensionAttributesInterface
{
    /**
     * Refunded and invoiced subscription initial fee column names
     */
    const EXT_ATTRIBUTE_INITIAL_FEE_INVOICED = 'subs_initial_fee_invoiced';
    const EXT_ATTRIBUTE_BASE_INITIAL_FEE_INVOICED = 'base_subs_initial_fee_invoiced';
    const EXT_ATTRIBUTE_INITIAL_FEE_REFUNDED = 'subs_initial_fee_refunded';
    const EXT_ATTRIBUTE_BASE_INITIAL_FEE_REFUNDED = 'base_subs_initial_fee_refunded';

    /**
     * Gets base invoiced initial fee.
     *
     * @return string|null
     */
    public function getBaseSubsInitialFeeInvoiced();

    /**
     * Sets base invoiced initial fee.
     *
     * @param string|int $baseInitialFeeInvoiced
     * @return $this
     */
    public function setBaseSubsInitialFeeInvoiced($baseInitialFeeInvoiced);

    /**
     * Gets invoiced initial fee.
     *
     * @return string|null
     */
    public function getSubsInitialFeeInvoiced();

    /**
     * Sets invoiced initial fee.
     *
     * @param string|int $initialFeeInvoiced
     * @return $this
     */
    public function setSubsInitialFeeInvoiced($initialFeeInvoiced);

    /**
     * Gets refunded initial fee.
     *
     * @return string|null
     */
    public function getSubsInitialFeeRefunded();

    /**
     * Sets refunded initial fee.
     *
     * @param string|int $initialFeeRefunded
     * @return $this
     */
    public function setSubsInitialFeeRefunded($initialFeeRefunded);

    /**
     * Gets base refunded initial fee.
     *
     * @return string|null
     */
    public function getBaseSubsInitialFeeRefunded();

    /**
     * Sets base refunded initial fee.
     *
     * @param string|int $baseInitialFeeRefunded
     * @return $this
     */
    public function setBaseSubsInitialFeeRefunded($baseInitialFeeRefunded);
}
