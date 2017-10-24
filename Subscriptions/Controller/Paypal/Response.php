<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Paypal;

use TNW\Subscriptions\Controller\Adminhtml\Paypal\Response as Base;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout\Payment;

/**
 * Controller to processing response from PayPal gateway.
 */
class Response extends Base
{
    /**
     * @inheritdoc
     */
    protected function getFormIndex($profile = null)
    {
        return Payment::DATA_SCOPE_PAYMENT_FORM;
    }
}
