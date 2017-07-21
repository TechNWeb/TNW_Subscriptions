<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

use Magento\Quote\Model\QuoteFactory as ModelQuoteFactory;
use Magento\Customer\Api\GroupManagementInterface;
use TNW\Subscriptions\Model\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use TNW\Subscriptions\Model\Backend\Session\Quote as Session;
use Magento\Quote\Model\Quote as ModelQuote;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\AbstractCreate;

class Quote extends AbstractCreate
{
    /**
     * Factory for creating quotes.
     *
     * @var ModelQuoteFactory
     */
    private $quoteFactory;

    /**
     * Customer group manager.
     *
     * @var GroupManagementInterface
     */
    private $groupManagement;

    /**
     * Quote addresses creator.
     *
     * @var Address
     */
    private $addressCreator;

    /**
     * Repository for retrieving quotes.
     *
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * Repository for retrieving customers.
     *
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Quote constructor.
     * @param Context $context
     * @param Session $session
     * @param ModelQuoteFactory $quoteFactory
     * @param GroupManagementInterface $groupManagement
     * @param Address $addressCreator
     * @param CartRepositoryInterface $cartRepository
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        Session $session,
        ModelQuoteFactory $quoteFactory,
        GroupManagementInterface $groupManagement,
        Address $addressCreator,
        CartRepositoryInterface $cartRepository,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->groupManagement = $groupManagement;
        $this->addressCreator = $addressCreator;
        $this->cartRepository = $cartRepository;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $session);
    }

    /**
     * Returns repository for retrieving quotes.
     *
     * @return CartRepositoryInterface
     */
    public function getCartRepository()
    {
        return $this->cartRepository;
    }

    /**
     * Creates empty quote and assigns customer if there is a customer id in session.
     *
     * @return int|string
     */
    public function createSubCart()
    {
        /** @var ModelQuote $quote */
        $quote = $this->quoteFactory->create();

        if ($this->getSession()->getStoreId()) {
            $quote->setCustomerGroupId($this->groupManagement->getDefaultGroup()->getId());
            $quote->setIsActive(false);
            $quote->setStoreId($this->getSession()->getStoreId());

            if (!$this->getSession()->getCustomerId()) {
                $quote->setBillingAddress($this->addressCreator->getEmptyAddress());
                $quote->setShippingAddress($this->addressCreator->getEmptyAddress());
            }

            $this->cartRepository->save($quote);
            $quote = $this->cartRepository->get($quote->getId(), [$this->getSession()->getStoreId()]);

            if ($this->getSession()->getCustomerId() &&
                $this->getSession()->getCustomerId() !== $quote->getCustomerId()
            ) {
                $customer = $this->customerRepository->getById($this->getSession()->getCustomerId());
                $quote->assignCustomer($customer);
                $quote->setTotalsCollectedFlag(true);
                $this->cartRepository->save($quote);
            }
        }

        $quote->setData('ignore_old_qty', true);
        $quote->setData('is_super_mode', true);

        return $quote;
    }
}