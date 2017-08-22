<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Customer\Model\Group;
use TNW\Subscriptions\Model\Backend\Session\Quote;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Class subscription and account information modifier
 */
class SubscriptionAndAccountInformation implements ModifierInterface
{
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
     * Profile status
     *
     * @var ProfileStatus
     */
    private $profileStatus;

    /**
     * Modifier constructor.
     *
     * @param Quote $session
     * @param Group $group
     */
    public function __construct(
        Quote $session,
        Group $group,
        ProfileStatus $profileStatus
    ) {
        $this->session = $session;
        $this->customerGroup = $group;
        $this->profileStatus = $profileStatus;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $currentStatusLabel = $this->profileStatus->getLabelByValue(ProfileStatus::STATUS_PENDING);

        $data[Payment::FORM_DATA_VALUE] = [
            'website_information' => $this->getCurrentWebsiteName(),
            'currency_information' => $this->getCurrentCurrencyCode(),
            'status_information' => $currentStatusLabel,
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
        return $meta;
    }

    /**
     * Get current selected website name
     *
     * @return mixed|string
     */
    private function getCurrentWebsiteName()
    {
        return $this->getFirstQuote()->getStore()->getWebsite()->getName();
    }

    /**
     * Get current selected currency code
     *
     * @return mixed|string
     */
    private function getCurrentCurrencyCode()
    {
        return $this->getFirstQuote()->getQuoteCurrencyCode();
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
        return $this->getFirstQuote()->getCustomerFirstname() . ' ' . $this->firstQuote->getCustomerLastname();
    }

    /**
     * Get current customer email
     *
     * @return string
     */
    private function getCustomerEmail()
    {
        return $this->getFirstQuote()->getCustomerEmail();
    }

    /**
     * Return customer group name
     *
     * @return string
     */
    private function getCustomerGroupName()
    {
        $customerGroupId = $this->getFirstQuote()->getCustomerGroupId();
        $currentCustomerGroup = $this->customerGroup->load($customerGroupId);

        return $currentCustomerGroup->getCode();
    }

    /**
     * Retunr first quote from subscription
     *
     * @return \Magento\Quote\Model\Quote
     */
    private function getFirstQuote()
    {
        return $this->session->getFirstQuote();
    }
}