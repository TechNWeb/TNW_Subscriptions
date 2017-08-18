<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Customer\Model\Group;
use TNW\Subscriptions\Model\Backend\Session\Quote;

class SubscriptionAndAccountInformation implements ModifierInterface
{
    /**#@+
     * Form request values
     */
    const FORM_DATA_KEY = 'subscription_and_account_information';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    /**
     * Admin session.
     *
     * @var Quote
     */
    private $session;

    /**
     * Customer Group
     *
     * @var Group
     */
    private $customerGroup;

    /**
     * First subscription quote.
     *
     * @var Quote
     */
    private $firstQuote;

    /**
     * Modifier constructor.
     *
     * @param Quote $session
     */
    public function __construct(
        Quote $session,
        Group $group
    ) {
        $this->session = $session;
        $this->customerGroup = $group;
        $this->firstQuote = $session->getFirstQuote();
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $data[self::FORM_DATA_VALUE] = [
            'website_information' => $this->getCurrentWebsiteName(),
            'currency_information' => $this->getCurrentCurrencyCode(),
            'status_information' => __('Pending'),
            'customer_name_information' => $this->getCustomerFullName(),
            'email_information' => $this->getCustomerEmail(),
            'customer_group_information' => $this->getCustomerGroupName(),
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta = [];
        return $meta;
    }

    /**
     * Get current selected website name
     *
     * @return mixed|string
     */
    private function getCurrentWebsiteName()
    {
        return $this->firstQuote->getStore()->getWebsite()->getName();
    }

    /**
     * Get current selected currency code
     *
     * @return mixed|string
     */
    private function getCurrentCurrencyCode()
    {
        return $this->firstQuote->getQuoteCurrencyCode();
    }

    /**
     * Get current customer full name
     * first name + last name
     * e.g. "John Smith"
     *
     * @return string
     */
    private function getCustomerFullName()
    {
        return $this->firstQuote->getCustomerFirstname() . ' ' . $this->firstQuote->getCustomerLastname();
    }

    /**
     * Get current customer email
     *
     * @return string
     */
    private function getCustomerEmail()
    {
        return $this->firstQuote->getCustomerEmail();
    }

    /**
     * Return customer group name
     *
     * @return string
     */
    private function getCustomerGroupName()
    {
        $customerGroupId = $this->firstQuote->getCustomerGroupId();
        $currentCustomerGroup = $this->customerGroup->load($customerGroupId);

        return $currentCustomerGroup->getCode();
    }
}