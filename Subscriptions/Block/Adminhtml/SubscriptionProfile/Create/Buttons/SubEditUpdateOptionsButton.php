<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryProductsForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

/**
 * Update options button configuration.
 */
class SubEditUpdateOptionsButton extends UpdateOptionsButton
{
    /**
     * @inheritdoc
     */
    protected function getEditOptionsModal()
    {
        return SummaryProductsForm::DATA_SCOPE_MODAL_FORM . '.'
        . SummaryProductsForm::DATA_SCOPE_MODAL_FORM
        . '.editOptionsModal';
    }

    /**
     * @inheritdoc
     */
    protected function getTargetName()
    {
        return $this->getEditOptionsModal();
    }
}
