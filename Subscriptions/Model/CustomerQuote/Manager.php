<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\CustomerQuote;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Api\CustomerQuoteRepositoryInterface;
use TNW\Subscriptions\Api\Data\CustomerQuoteInterface;
use TNW\Subscriptions\Model\CustomerQuote;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Customer subscriptions manager
 */
class Manager
{
    /**
     * Repository fore saving/retrieving quotes.
     *
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * Search criteria builder
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Customer quote repository
     *
     * @var CustomerQuoteRepositoryInterface
     */
    private $customerQuoteRepository;

    /**
     * Profile creator
     *
     * @var CreateProfile
     */
    private $createProfile;

    /**
     * Subscription session
     *
     * @var QuoteSessionInterface
     */
    private $session;

    /**

     * @param CartRepositoryInterface $cartRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerQuoteRepositoryInterface $customerQuoteRepository
     * @param CreateProfile $createProfile
     * @param QuoteSessionInterface $session
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerQuoteRepositoryInterface $customerQuoteRepository,
        CreateProfile $createProfile,
        QuoteSessionInterface $session
    ) {
        $this->cartRepository = $cartRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerQuoteRepository = $customerQuoteRepository;
        $this->createProfile = $createProfile;
        $this->session = $session;
    }

    /**
     * Returns customer quotes.
     *
     * @param int|string $customerId
     * @return CartInterface[]
     */
    public function getQuotesByCustomer($customerId)
    {
        $quoteIds = [];
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            CustomerQuote::CUSTOMER_ID,
            $customerId
        )->create();
        /** @var CustomerQuoteInterface[] $customerQuotes */
        $customerQuotes = $this->customerQuoteRepository->getList($searchCriteria)->getItems();
        /** @var CustomerQuoteInterface $customerQuote */
        foreach ($customerQuotes as $customerQuote) {
            $quoteIds[] = $customerQuote->getQuoteId();
        }

        return $this->getQuotes($quoteIds);
    }

    /**
     * Save customer quote by customer id and quote id.
     *
     * @param string $customerId
     * @param string $quoteId
     * @return void
     */
    public function saveCustomerQuote($customerId, $quoteId)
    {
        $this->customerQuoteRepository->saveCustomerQuote($customerId, $quoteId);
    }

    /**
     * Updates customer quote table with given items, deletes old items.
     *
     * @param array $quoteIds
     * @param string $customerId
     * @return bool
     */
    public function updateItems(
        array $quoteIds,
        $customerId
    ) {
        if (!$customerId) {
            return false;
        }
        if (!$quoteIds) {
            $quoteIds = [];
        }
        if (!is_array($quoteIds)) {
            $quoteIds = [$quoteIds];
        }
        $normalizedQuoteIds = [];
        foreach ($quoteIds as $quoteId) {
            $normalizedQuoteIds[] = (int)$quoteId;
        }
        /** @var CustomerQuoteInterface $customerQuote */
        foreach ($this->customerQuoteRepository->getCustomerQuotes($customerId) as $customerQuote) {
            if (!in_array((int)$customerQuote->getQuoteId(), $normalizedQuoteIds, true)) {
                $this->customerQuoteRepository->delete($customerQuote);
            } else {
                $normalizedQuoteIds = array_diff($normalizedQuoteIds, [(int)$customerQuote->getQuoteId()]);
            }
        }
        foreach ($normalizedQuoteIds as $quoteId) {
            $this->customerQuoteRepository->saveCustomerQuote($customerId, $quoteId);
        }
        return true;
    }

    /**
     * Load data for customer sub quotes and merge with current sub quotes
     *
     * @return $this
     */
    public function loadCustomerSubQuote()
    {
        $customerId =$this->session->getCustomerId();
        if (!$customerId) {
            return $this;
        }
        try {
            /** @var CartInterface[] $customerQuotes */
            $quotes = $this->getQuotesByCustomer($customerId);
        } catch (NoSuchEntityException $e) {
            $quotes = [];
        }
        if (count($quotes)) {
            $this->mergeSubQuotes($quotes);
        }
        $this->updateItems($this->session->getSubQuoteIds(), $customerId);
        return $this;
    }

    /**
     * Merges customer quotes with visitor quotes
     *
     * @param CartInterface[] $quotes
     * @return $this
     */
    private function mergeSubQuotes($quotes)
    {
        $newQuotesIds = [];
        /** @var CartInterface $quote */
        foreach ($quotes as $quote) {
            $newQuotesIds[] = $quote->getId();
        }
        $currentSubQuoteIds = $this->session->getSubQuoteIds() ?: [];
        $this->session->setSubQuoteIds(array_merge($newQuotesIds, $currentSubQuoteIds));
        $this->createProfile->setSubQuotes($this->session->getSubQuotes());
        $this->createProfile->recollectUnmodifiedQuotes();
        return $this;
    }

    /**
     * Loads quotes by ids.
     *
     * @param array $quoteIds
     * @return CartInterface[]
     */
    private function getQuotes(array $quoteIds)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            Quote::KEY_ENTITY_ID,
            $quoteIds,
            'in'
        )->create();
        return $this->cartRepository->getList($searchCriteria)->getItems();
    }
}
