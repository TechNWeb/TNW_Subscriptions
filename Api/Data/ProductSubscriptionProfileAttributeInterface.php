<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

/**
 * Interface for subscription profile product attribute.
 */
interface ProductSubscriptionProfileAttributeInterface extends \Magento\Eav\Api\Data\AttributeInterface
{
    const IS_VISIBLE_ON_FRONT = 'is_visible_on_front';

    /**
     * Whether the attribute is visible on the frontend
     *
     * @return string|null
     */
    public function getIsVisibleOnFront();

    /**
     * Set whether the attribute is visible on the frontend
     *
     * @param string $isVisibleOnFront
     * @return $this
     */
    public function setIsVisibleOnFront($isVisibleOnFront);
}
