<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Model\Order\Email\Container;

use Magento\Sales\Model\Order\Email\Container\OrderIdentity as OrderEmailIdentity;

/**
 * Class OrderIdentity - for emails
 */
class OrderIdentity extends OrderEmailIdentity
{
    const XML_TNW_SUBSCRIPTIONS_PATH_EMAIL_TEMPLATE = 'tnw_subscriptions_profile_options/emails/confirmation_order';
    const XML_TNW_SUBSCRIPTIONS_EMAIL_ENABLED = 'tnw_subscriptions_profile_options/email_config/confirmation_order';

    /**
     * Return template id
     *
     * @return mixed
     */
    public function getTemplateId()
    {
        return $this->getConfigValue(
            self::XML_TNW_SUBSCRIPTIONS_PATH_EMAIL_TEMPLATE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Is email enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_TNW_SUBSCRIPTIONS_EMAIL_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getStoreId()
        );
    }
}
