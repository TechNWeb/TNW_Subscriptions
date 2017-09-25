<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;

/**
 * Data provider for cancel button form in popup.
 */
class CancelButtonPopup extends AbstractDataProvider
{
    /**
     * Form data scope.
     */
    const DATA_SCOPE_CANCEL_BUTTON_MODAL_FORM = 'tnw_subscriptionprofile_cancel_button_popup_form';

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
    }
}
