<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Model\Order\Email\Container;

use Magento\Sales\Model\Order\Email\Container\OrderIdentity as OrderEmailIdentity;
use TNW\Subscriptions\Model\EmailNotifier;

/**
 * Class OrderIdentity - for emails
 */
class OrderIdentity extends OrderEmailIdentity
{
    const XML_TNW_SUBSCRIPTIONS_PATH_EMAIL_TEMPLATE =
        'tnw_subscriptions_profile_notification/confirmation_order_setting/confirmation_order';
    const XML_TNW_SUBSCRIPTIONS_EMAIL_ENABLED =
        'tnw_subscriptions_profile_notification/confirmation_order_setting/confirmation_order_enable';
    const XML_TNW_SUBSCRIPTIONS_PATH_EMAIL_COPY_METHOD =
        'tnw_subscriptions_profile_notification/confirmation_order_setting/confirmation_order_copy_method';
    const XML_TNW_SUBSCRIPTIONS_PATH_EMAIL_COPY_TO =
        'tnw_subscriptions_profile_notification/confirmation_order_setting/confirmation_order_copy_to';

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
        ) && $this->scopeConfig->isSetFlag(
                EmailNotifier::XML_PATH_MODULE_ENABLE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->getStore()->getStoreId()
            );
    }

    /**
     * Return copy method
     *
     * @return mixed
     */
    public function getCopyMethod()
    {
        return $this->getConfigValue(
            self::XML_TNW_SUBSCRIPTIONS_PATH_EMAIL_COPY_METHOD,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Return email copy_to list
     *
     * @return array|bool
     */
    public function getEmailCopyTo()
    {
        $data = $this->getConfigValue(self::XML_TNW_SUBSCRIPTIONS_PATH_EMAIL_COPY_TO, $this->getStore()->getStoreId());
        if (!empty($data)) {
            return array_map('trim', explode(',', $data));
        }
        return false;
    }
}
