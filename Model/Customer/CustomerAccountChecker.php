<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use TNW\Subscriptions\Api\CustomerAccountCheckerInterface;

/**
 * Class CustomerAccountChecker - Check force login status for guest user
 */
class CustomerAccountChecker implements CustomerAccountCheckerInterface
{
    const IS_CUSTOMER_GUEST = 0;
    const IS_CUSTOMER_EXISTS = 8;
    const IS_SUBSCRIBE_ACTIVE = 16;

    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * CustomerAccountChecker constructor.
     * @param AccountManagementInterface $accountManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        AccountManagementInterface $accountManagement,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CartRepositoryInterface $cartRepository
    ) {
        $this->accountManagement = $accountManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $cartRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function isCustomerExistsAndShouldBeLoggedIn(string $customerEmail, string $cartId = null): int
    {
        $result = self::IS_CUSTOMER_GUEST;
        if (!$this->accountManagement->isEmailAvailable($customerEmail)) {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());

            $result = self::IS_CUSTOMER_EXISTS;
            foreach ($quote->getItems() as $item) {
                $options = $item->getBuyRequest();
                if (!isset($options['subscribe_active']) || !$options['subscribe_active']) {
                    continue;
                }
                $result = self::IS_CUSTOMER_EXISTS | self::IS_SUBSCRIBE_ACTIVE;
            }
        }
        return (int)$result;
    }
}
