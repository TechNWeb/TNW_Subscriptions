<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\CustomerQuote;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Api\CustomerQuoteRepositoryInterface;
use TNW\Subscriptions\Api\Data\CustomerQuoteInterface;
use TNW\Subscriptions\Model\CustomerQuote;
use TNW\Subscriptions\Model\ResourceModel\Queue\CollectionFactory;
use TNW\Subscriptions\Model\CustomerQuoteFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Class Manager
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
     * Customer quote factory
     *
     * @var CustomerQuoteFactory
     */
    private $customerQuoteFactory;

    /**
     * Profile creator
     *
     * @var CreateProfile
     */
    private $createProfile;

    /**
     * Manager constructor.
     * @param CartRepositoryInterface $cartRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerQuoteRepositoryInterface $customerQuoteRepository
     * @param CustomerQuoteFactory $customerQuoteFactory
     * @param CreateProfile $createProfile
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerQuoteRepositoryInterface $customerQuoteRepository,
        CustomerQuoteFactory $customerQuoteFactory,
        CreateProfile $createProfile
    ) {
        $this->cartRepository = $cartRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerQuoteRepository = $customerQuoteRepository;
        $this->customerQuoteFactory = $customerQuoteFactory;
        $this->createProfile = $createProfile;
    }

    /**
     * Returns customer quotes
     *
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
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            Quote::KEY_ENTITY_ID,
            $quoteIds,
            'in'
        )->create();
        return $this->cartRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Save customer quote by customer id and quote id
     *
     * @param string $customerId
     * @param string $quoteId
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
        $quoteIds,
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
        $customerId =$this->createProfile->getSession()->getCustomerId();
        if (!$customerId) {
            return $this;
        }
        try {
            /** @var CartInterface[] $customerQuotes */
            $quotes = $this->getQuotesByCustomer($customerId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $quotes = [];
        }
        if (count($quotes)) {
            $this->mergeSubQuotes($quotes);
        }
        $this->updateItems($this->createProfile->getSession()->getSubQuoteIds(), $customerId);
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
        $currentSubQuotes = $this->createProfile->getSession()->getSubQuotes();
        $this->createProfile->getSession()->setSubQuoteIds($newQuotesIds);
        /** @var Quote $currentSubQuote */
        foreach ($currentSubQuotes as $currentSubQuote) {
            /** @var Item $item */
            foreach ($currentSubQuote->getAllItems() as $item) {
                $this->createProfile->addToSubscription($this->getProductData($item));
            }
            $this->cartRepository->delete($currentSubQuote);
        }
        $this->createProfile->recollectUnmodifiedQuotes();
        return $this;
    }

    /**
     * Returns prepared data for adding to sub quote
     *
     * @param Item $item
     * @return array
     */
    private function getProductData(Item $item)
    {
        $buyRequest = $item->getBuyRequest()->getDataByPath(CreateProfile::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);
        $uniqueData = $buyRequest[CreateProfile::UNIQUE];
        $nonUniqueData = $buyRequest[CreateProfile::NON_UNIQUE];
        $result = [
            'billing_frequency' => $uniqueData['billing_frequency'],
            'period' => $uniqueData['period'],
            'term' => $uniqueData['term'],
            'start_on' => $uniqueData['start_on'],
            'product_id' => $item->getProduct()->getId(),
            'qty' => $item->getQty(),
        ];
        return $result;
    }
}
