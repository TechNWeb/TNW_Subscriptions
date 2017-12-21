<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Quote;

use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
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
     * @var State
     */
    private $state;

    /**
     * @param CreateProfile $createProfile
     * @param BillingFrequencyRepositoryInterface $frequencyRepository
     * @param State $state
     */
    public function __construct(
        CreateProfile $createProfile,
        BillingFrequencyRepositoryInterface $frequencyRepository,
        State $state
    ) {
        $this->createProfile = $createProfile;
        $this->frequencyRepository = $frequencyRepository;
        $this->state = $state;
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
                foreach ($quote->getAllVisibleItems() as $item) {
                    $productCanBeSubscribed = $this->productCanBeSubscribed($item);
                    if ($productCanBeSubscribed) {
                        $currentRequest = $this->getCurrentBuyRequest($item);
                        list($newRequest, $productsData) = $this->getNewBuyRequest($item, $currentRequest);
                        if ($currentRequest != $newRequest) {
                            // Remove quote item
                            if (!$this->createProfile->removeSubscriptions($item)) {
                                $result = $this->filterResult($result, $quote);
                            };
                            // Add updated quote item if buy request was changed
                            $this->createProfile->setSubQuotes($result);
                            $newItem = $this->createProfile->addToSubscription($productsData);
                            if ($newItem) {
                                $newQuote = $newItem->getQuote();
                                $result = $this->addQuoteToResult($result, $newQuote);
                            }
                        }
                    } else {
                        // Remove quote item
                        $this->addError(
                            __('Quote item was removed because of product cannot be subscribed anymore.')
                        );
                        if (!$this->createProfile->removeSubscriptions($item)) {
                            $result = $this->filterResult($result, $quote);
                            $this->addError([
                                __('Quote was removed because of last item removal.'),
                                'needReload' => true,
                            ]);
                        };
                    }
                }
            } else {
                $result = $this->filterResult($result, $quote);
                $this->createProfile->getQuoteCreator()->getCartRepository()->delete($quote);
                $this->addError([
                    __('Quote was removed because of billing frequency removal.'),
                    'needReload' => true,
                ]);
            }
        }

        return $result;
    }

    /**
     * Add error.
     * It adds to quote session.
     *
     * @param string|array $error
     * @return void
     */
    private function addError($error)
    {
        if (!empty($error)) {
            $this->getSession()->addError($error);
        }
    }

    /**
     * Check if product can be subscribed.
     * Product can be subscribed if it is "in stock" and is NOT "One time purchase.
     *
     * @param Item $item
     * @return bool
     */
    private function productCanBeSubscribed($item)
    {
        $result = false;
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $item->getProduct();
        if ($product) {
            $purchaseType = $product->getData(\TNW\Subscriptions\Model\Product\Attribute::SUBSCRIPTION_PURCHASE_TYPE);
            $result =
                $product
                && $product->getIsSalable() // Use because of TNW\Subscriptions\Block\Product\View\Subscribe (line 148)
                && $purchaseType != PurchaseType::ONE_TIME_PURCHASE_TYPE;
        }

        return $result;
    }

    /**
     * Returns new buy request for quote item, depends on old one.
     *
     * @param Item $item
     * @param array $currentRequest
     * @return array
     */
    private function getNewBuyRequest(Item $item, array $currentRequest)
    {
        $productModifier = $this->createProfile->getProductModifier();
        $productsData = [
            'billing_frequency' => $currentRequest[CreateProfile::UNIQUE]['billing_frequency'],
            'period' => $currentRequest[CreateProfile::UNIQUE]['period'],
            'term' => $currentRequest[CreateProfile::UNIQUE]['term'],
            'product_id' => $item->getProduct()->getId(),
            'start_on' => $currentRequest[CreateProfile::UNIQUE]['start_on'],
            'qty' => $item->getQty(),
        ];
        switch ($item->getProductType()) {
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $additionalParams['super_attribute'] = $item->getBuyRequest()->getDataByPath('super_attribute');
                break;
            default:
                $additionalParams = [];
                break;
        }
        $productsData = array_merge($productsData, $additionalParams);
        $productModifier->setData($productsData);
        $productModifier->setProduct($item->getProduct());
        $result = $productModifier->getPreparedBuyRequest(true)->getData(
            CreateProfile::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME
        );
        if ($this->state->getAreaCode() !== \Magento\Framework\App\Area::AREA_FRONTEND) {
            unset($result[CreateProfile::NON_UNIQUE]);
        } else {
            //round current price to 2 signs after point
            if (isset($result[CreateProfile::NON_UNIQUE]['current_price'])) {
                $result[CreateProfile::NON_UNIQUE]['current_price'] =
                    round($result[CreateProfile::NON_UNIQUE]['current_price'], 2);
            }
        }
        //unset from request full request flag, for validation we don't need it
        unset($result[CreateProfile::FULL_REQUEST_PARAM_NAME]);
        //unset from request preset qty price, for validation we need only current preset qty price
        unset($result[CreateProfile::NON_UNIQUE]['preset_qty_price']);

        return [$result, $productsData];
    }

    /**
     * Returns current buy request for quote item.
     *
     * @param Item $item
     * @return mixed
     */
    private function getCurrentBuyRequest(Item $item)
    {
        $request = $item->getBuyRequest()->getData(
            CreateProfile::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME
        );
        if ($this->state->getAreaCode() === \Magento\Framework\App\Area::AREA_FRONTEND) {
            $initialFees = $item->getExtensionAttributes()
                ? $item->getExtensionAttributes()->getSubsInitialFees()
                : null;
            $fee = $initialFees ? $initialFees->getSubsInitialFee() : 0;
            $request[CreateProfile::NON_UNIQUE]['current_price'] = (float)$item->getPrice();
            $request[CreateProfile::NON_UNIQUE]['initial_fee'] = $fee;
            $request[CreateProfile::NON_UNIQUE]['current_preset_qty_price'] = $item->getRowTotal();
            //unset from request preset qty price, for validation we need only current preset qty price
            unset($request[CreateProfile::NON_UNIQUE]['preset_qty_price']);
        } else {
            unset($request[CreateProfile::NON_UNIQUE]);
        }
        //unset from request full request flag, for validation we don't need it
        unset($request[CreateProfile::FULL_REQUEST_PARAM_NAME]);

        return $request;
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
        $items = $quote->getAllVisibleItems();
        if ($items) {
            /** @var Item $firstItem */
            $firstItem = reset($items);
            $frequencyIdPath = CreateProfile::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME
                . DIRECTORY_SEPARATOR . CreateProfile::UNIQUE
                . DIRECTORY_SEPARATOR . 'billing_frequency';
            $frequencyId = $firstItem->getBuyRequest()->getDataByPath($frequencyIdPath);
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
     * Filters result quotes. It removes given $quote from $result array.
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
