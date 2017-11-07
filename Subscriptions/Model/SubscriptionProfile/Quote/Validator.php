<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Subscription quotes validator.
 */
class Validator
{
    /**
     * Subscription creator.
     *
     * @var CreateProfile
     */
    private $createProfile;

    /**
     * Repository for retrieving billing frequencies.
     *
     * @var BillingFrequencyRepositoryInterface
     */
    private $frequencyRepository;

    /**
     * Subscription session.
     *
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @param CreateProfile $createProfile
     * @param BillingFrequencyRepositoryInterface $frequencyRepository
     */
    public function __construct(
        CreateProfile $createProfile,
        BillingFrequencyRepositoryInterface $frequencyRepository
    ) {
        $this->createProfile = $createProfile;
        $this->frequencyRepository = $frequencyRepository;
    }

    /**
     * Sets subscription session.
     *
     * @param QuoteSessionInterface $session
     * @return $this
     */
    public function setSession(QuoteSessionInterface $session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * Gets subscription session.
     *
     * @return QuoteSessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Validates and recalculates subscription quotes if need it.
     *
     * @param Quote[] $quotes
     * @return Quote[]
     */
    public function validate(array $quotes)
    {
        $result = $quotes;
        foreach ($quotes as $quote) {
            if ($this->isBillingFrequencyExists($quote)) {
                /** @var Item $item */
                foreach ($quote->getAllItems() as $item) {
                    $currentRequest = $this->getCurrentBuyRequest($item);
                    $productsData = $this->getProductsData($item, $currentRequest);
                    $newRequest = $this->getNewBuyRequest($productsData);
                    if ($currentRequest != $newRequest) {
                        if (!$this->createProfile->removeSubscriptions($item)) {
                            $result = $this->filterResult($result, $quote);
                        };
                        $this->createProfile->setSubQuotes($result);
                        $newItem = $this->createProfile->addToSubscription(
                            $this->getProductsData($item, $newRequest)
                        );
                        if ($newItem) {
                            $newQuote = $newItem->getQuote();
                            $result = $this->addQuoteToResult($result, $newQuote);
                        }
                    }
                }
            } else {
                $result = $this->filterResult($result, $quote);
                $this->createProfile->getQuoteCreator()->getCartRepository()->delete($quote);
            }
        }

        return $result;
    }

    /**
     * Returns new subscription item buy request, depends for old one.
     *
     * @param array $productsData
     * @return array
     */
    private function getNewBuyRequest(array $productsData)
    {
        $productModifier = $this->createProfile->getProductModifier();
        $productModifier->setData($productsData);

        $result = $productModifier->getPreparedBuyRequest()->getData(
            CreateProfile::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME
        );

        return $result;
    }

    /**
     * Returns current subscription item buy request.
     *
     * @param Item $item
     * @return mixed
     */
    private function getCurrentBuyRequest(Item $item)
    {
        return $item->getBuyRequest()->getData(
            CreateProfile::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME
        );
    }

    /**
     * Returns products data for buy request.
     *
     * @param Item $item
     * @param array $currentRequest
     * @return array
     */
    private function getProductsData(Item $item, array $currentRequest)
    {
        return [
            'billing_frequency' => $currentRequest[CreateProfile::UNIQUE]['billing_frequency'],
            'period' => $currentRequest[CreateProfile::UNIQUE]['period'],
            'term' => $currentRequest[CreateProfile::UNIQUE]['term'],
            'product_id' => $item->getProduct()->getId(),
            'start_on' => $currentRequest[CreateProfile::UNIQUE]['start_on'],
            'qty' => $item->getQty(),
        ];
    }

    /**
     * Checks if billing frequency exists.
     *
     * @param Quote $quote
     * @return bool
     */
    public function isBillingFrequencyExists(Quote $quote)
    {
        $result = false;
        $items = $quote->getAllItems();
        if ($items) {
            /** @var Item $firstItem */
            $firstItem = reset($items);
            $frequnecyIdPath = CreateProfile::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME
                . DIRECTORY_SEPARATOR . CreateProfile::UNIQUE
                . DIRECTORY_SEPARATOR . 'billing_frequency';
            $frequencyId = $firstItem->getBuyRequest()->getDataByPath($frequnecyIdPath);
            try {
                $this->frequencyRepository->getById($frequencyId);
                $result = true;
            } catch (NoSuchEntityException $e) {
                $this->createProfile->getContext()->log($e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Filters result quotes.
     *
     * @param Quote[] $result
     * @param Quote $quote
     * @return array
     */
    private function filterResult(array $result, Quote $quote)
    {
        $result = array_filter(
            $result,
            function ($subQuote) use ($quote) {
                return ($subQuote->getId() !== $quote->getId());
            }
        );
        return $result;
    }

    /**
     * Adds to result new quote.
     *
     * @param Quote[] $result
     * @param Quote $newQuote
     * @return array
     */
    private function addQuoteToResult(array $result, Quote $newQuote)
    {
        $needAddQuote = true;
        foreach ($result as $oldQuote) {
            if ($oldQuote->getId() == $newQuote->getId()) {
                $needAddQuote = false;
            }
        }
        if ($needAddQuote) {
            $result[] = $newQuote;
        }
        return $result;
    }
}
