<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Paypal;

use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Controller\Adminhtml\Paypal\Response as Base;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout\Payment;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Customer\Account\PaymentMethodForm;

/**
 * Controller to processing response from PayPal gateway.
 */
class Response extends Base
{
    /**
     * Returns response form index.
     *
     * @param null|SubscriptionProfileInterface $profile
     * @return string
     */
    protected function getFormIndex($profile = null)
    {
        $index = isset($profile)
            ? PaymentMethodForm::FORM_NAME
            : Payment::DATA_SCOPE_PAYMENT_FORM;

        return $index;
    }
}
