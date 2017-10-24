<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout;

use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment as BasePayment;

/**
 * Payment step form data provider.
 */
class Payment extends BasePayment
{
    /**
     * Form data scope
     */
    const DATA_SCOPE_PAYMENT_FORM = 'tnw_subscriptionprofile_checkout_payment_form';


    /**
     * @inheritdoc
     */
    public function getConfigData()
    {
        $configData = parent::getConfigData();
        $configData['submit_url'] = $this->urlBuilder->getUrl(
            '*/subscription/save'
        );
        $configData['process_url'] = $this->urlBuilder->getUrl(
            '*/subscription_create/process'
        );

        return $configData;
    }
}
