<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\System\Config;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * Class ShippingMethodsComment - adminhtml block for config shipping methods comments
 */
class ShippingMethodsComment extends AbstractBlock implements CommentInterface
{
    /**
     * @param string $elementValue
     * @return \Magento\Framework\Phrase|string
     */
    public function getCommentText($elementValue)
    {
        $comment = "This is a default available shipping method mPower will use if an order cannot be created using ";
        $comment .= "the shipping method from the subscription profile and an alternate shipping method is needed. ";
        $comment .= "If you don't see a payment method you want to use in this list, make sure it's enabled under ";
        $comment .= "<a href='%1'>Shipping Methods</a> configuration in Magento.<br>";
        $comment .= "<span style='color:#d04437'>IMPORTANT: </span> If the default shipping method won't be available";
        $comment .= " for an order, mPower will substitute it with the cheapest available shipping method.";
        return __($comment, $this->_urlBuilder->getUrl(
            'adminhtml/system_config/edit',
            ['section' => 'carriers']
        ));
    }
}
