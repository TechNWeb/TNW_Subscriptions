<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

/**
 * Interface for subscription profile product attribute.
 */
interface SubscriptionProfileAttributeInterface extends \Magento\Eav\Api\Data\AttributeInterface
{
    /**
     * Is visible on front field name
     */
    const IS_VISIBLE_ON_FRONT = 'is_visible_on_front';

    /**
     * Is wysiwyg enabled
     */
    const IS_WYSIWYG_ENABLED = 'is_wysiwyg_enabled';

    /**
     * Is pagebuilder enabled
     */
    const IS_PAGEBUILDER_ENABLED = 'is_pagebuilder_enabled';

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
