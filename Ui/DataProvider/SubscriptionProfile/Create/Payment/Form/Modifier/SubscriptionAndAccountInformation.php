<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Customer\Model\GroupRegistry;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;

/**
 * Class subscription and account information modifier.
 */
class SubscriptionAndAccountInformation implements ModifierInterface
{
    /**
     * Admin session.
     *
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * Profile status.
     *
     * @var ProfileStatus
     */
    private $profileStatus;

    /**
     * Customer group.
     *
     * @var GroupRegistry
     */
    private $groupRegistry;

    /**
     * SubscriptionAndAccountInformation constructor.
     *
     * @param QuoteSessionInterface $session
     * @param ProfileStatus $profileStatus
     * @param GroupRegistry $groupRegistry
     */
    public function __construct(
        QuoteSessionInterface $session,
        ProfileStatus $profileStatus,
        GroupRegistry $groupRegistry
    ) {
        $this->session = $session;
        $this->profileStatus = $profileStatus;
        $this->groupRegistry = $groupRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $currentStatusLabel = $this->profileStatus->getLabelByValue(ProfileStatus::STATUS_PENDING);

        $data[Payment::PAYMENT_FORM_DATA_VALUE] = [
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
     * Get current selected website name.
     *
     * @return string
     */
    private function getCurrentWebsiteName()
    {
        return $this->getFirstQuote()->getStore()->getWebsite()->getName();
    }

    /**
     * Get current selected currency code.
     *
     * @return string
     */
    private function getCurrentCurrencyCode()
    {
        return $this->getFirstQuote()->getQuoteCurrencyCode();
    }

    /**
     * Get current customer full name.
     * first name + last name.
     * e.g. "John Smith".
     *
     * @return string
     */
    private function getCustomerFullName()
    {
        if ($this->getFirstQuote()->getCustomerId()) {
            $result = $this->getFirstQuote()->getCustomerFirstname()
                . ' '
                . $this->getFirstQuote()->getCustomerLastname();
        } else {
            /** @var \Magento\Quote\Model\Quote\Address\ $shippingAddress */
            $shippingAddress = $this->getFirstQuote()->getShippingAddress();
            $result = $shippingAddress->getFirstname()
                . ' '
                . $shippingAddress->getLastname();
        }

        return $result;
    }

    /**
     * Get current customer email.
     *
     * @return string
     */
    private function getCustomerEmail()
    {
        $result = $this->session->getCustomerEmail();
        if ($this->getFirstQuote()->getCustomerId()) {
            $result = $this->getFirstQuote()->getCustomerEmail();
        }

        return $result;
    }

    /**
     * Return customer group name.
     *
     * @return string
     */
    private function getCustomerGroupName()
    {
        $customerGroupId = $this->session->getCustomerGroup();
        if ($this->getFirstQuote()->getCustomerId()) {
            $customerGroupId = $this->getFirstQuote()->getCustomerGroupId();

        }
        $currentCustomerGroup = $this->groupRegistry->retrieve($customerGroupId);

        return $currentCustomerGroup->getCode();
    }

    /**
     * Return first quote from subscription.
     *
     * @return \Magento\Quote\Model\Quote
     */
    private function getFirstQuote()
    {
        return $this->session->getFirstQuote();
    }

    /**
     * Returns modified form config.
     */
    public function modifyConfigData(array $configData)
    {
        return $configData;
    }
}
