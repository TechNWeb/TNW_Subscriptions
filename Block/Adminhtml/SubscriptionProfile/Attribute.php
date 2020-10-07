<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile;

/**
 * Adminhtml subscription profile attributes block
 */
class Attribute extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_subscriptionProfile_attribute';
        $this->_blockGroup = 'TNW_Subscriptions';
        $this->_headerText = __('Subscription Profile Attributes');
        $this->_addButtonLabel = __('Add New Attribute');
        parent::_construct();
    }
}
