<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Block\Order\Email;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Block\Order\Email\Items;

/**
 * Plugin to render additional blocks after order items
 * There is no other way to render them via layout instructions after 'items' block
 */
class EmailItemsAfter
{
    /**
     * @param Items $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(Items $subject, string $result): string
    {
        try {
            $afterItemsBlock = $subject->getLayout()->getBlock('after_items');
        } catch (LocalizedException $e) {
            return $result;
        }
        if ($afterItemsBlock) {
            $result .= $afterItemsBlock->toHtml();
        }
        return $result;
    }
}
