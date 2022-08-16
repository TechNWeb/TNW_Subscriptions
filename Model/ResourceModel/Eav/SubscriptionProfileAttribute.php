<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\Eav;

use Magento\Eav\Model\Entity\Attribute;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAttributeInterface;

/**
 * Product subscription profile attribute model
 */
class SubscriptionProfileAttribute extends Attribute implements SubscriptionProfileAttributeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getIsVisibleOnFront()
    {
        return $this->getData(self::IS_VISIBLE_ON_FRONT);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsVisibleOnFront($isVisibleOnFront)
    {
        return $this->setData(self::IS_VISIBLE_ON_FRONT, $isVisibleOnFront);
    }

    /**
     * @codeCoverageIgnoreStart
     * {@inheritdoc}
     */
    public function getIsWysiwygEnabled()
    {
        return $this->getData(self::IS_WYSIWYG_ENABLED);
    }

    /**
     * Set whether WYSIWYG is enabled flag
     *
     * @param bool $isWysiwygEnabled
     * @return $this
     */
    public function setIsWysiwygEnabled($isWysiwygEnabled)
    {
        return $this->setData(self::IS_WYSIWYG_ENABLED, $isWysiwygEnabled);
    }

    /**
     * @codeCoverageIgnoreStart
     * {@inheritdoc}
     */
    public function getIsPagebuilderEnabled()
    {
        return $this->getData(self::IS_PAGEBUILDER_ENABLED);
    }

    /**
     * Set whether Pagebuilder is enabled flag
     *
     * @param bool $isPagebuilderEnabled
     * @return $this
     */
    public function setIsPagebuilderEnabled($isPagebuilderEnabled)
    {
        return $this->setData(self::IS_PAGEBUILDER_ENABLED, $isPagebuilderEnabled);
    }
}
