<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

use Magento\Paypal\Model\Payflowpro as Base;
use Magento\Store\Model\ScopeInterface;

/**
 * Payflow Pro payment gateway model
 */
class Payflowpro extends Base
{
    /**
     * @return array
     */
    public function getDebugReplacePrivateDataKeys()
    {
        return (array) $this->_debugReplacePrivateDataKeys;
    }

    /**
     * @return bool
     */
    public function getDebugFlag()
    {
        return $this->_scopeConfig->getValue(
            'payment/payflowpro/debug',
            ScopeInterface::SCOPE_STORE
        );
    }
}
