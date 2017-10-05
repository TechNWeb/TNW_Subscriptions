<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\State;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Session\SaveHandlerInterface;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\Session\SidResolverInterface;
use Magento\Framework\Session\StorageInterface;
use Magento\Framework\Session\ValidatorInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Abstract quote session for admin and storefront subscriptions quote sessions.
 *
 * @method QuoteSession setCustomerId($id)
 * @method QuoteSession setCurrencyId($currencyId)
 */
abstract class QuoteSession extends SessionManager implements QuoteSessionInterface
{
    /**
     * Quotes cache.
     *
     * @var ModelQuote[]
     */
    protected $quotes;

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Quote constructor.
     *
     * @param Http $request
     * @param SidResolverInterface $sidResolver
     * @param ConfigInterface $sessionConfig
     * @param SaveHandlerInterface $saveHandler
     * @param ValidatorInterface $validator
     * @param StorageInterface $storage
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param State $appState
     * @param CartRepositoryInterface $quoteRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Http $request,
        SidResolverInterface $sidResolver,
        ConfigInterface $sessionConfig,
        SaveHandlerInterface $saveHandler,
        ValidatorInterface $validator,
        StorageInterface $storage,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        State $appState,
        CartRepositoryInterface $quoteRepository,
        StoreManagerInterface $storeManager,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct(
            $request,
            $sidResolver,
            $sessionConfig,
            $saveHandler,
            $validator,
            $storage,
            $cookieManager,
            $cookieMetadataFactory,
            $appState
        );
        if ($this->storeManager->hasSingleStore()) {
            $this->setStoreId($this->storeManager->getStore(true)->getId());
        }
    }

    /**
     * @return int
     */
    abstract public function getCustomerId();

    /**
     * @return int
     */
    abstract public function getStoreId();

    /**
     * @return string
     */
    abstract public function getCurrencyId();

    /**
     * @return \Magento\Store\Api\Data\StoreInterface|Store
     */
    public function getStore()
    {
        if ($this->store === null && !empty($this->getStoreId())) {
            $this->store = $this->storeManager->getStore($this->getStoreId());
            $currencyId = $this->getCurrencyId();
            if ($currencyId) {
                $this->store->setCurrentCurrencyCode($currencyId);
            }
        }
        return $this->store;
    }

    /**
     * @param int|string $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storage->setStoreId($storeId);

        return $this;
    }

    /**
     * Returns list of subscription quote ids.
     *
     * @return array|null
     */
    public function getSubQuoteIds()
    {
        return $this->storage->getSubQuoteIds();
    }

    /**
     * Sets sub quote ids
     *
     * @param $subQuoteIds
     * @return mixed
     */
    public function setSubQuoteIds($subQuoteIds)
    {
        $this->storage->setSubQuoteIds($subQuoteIds);
        $this->removeAllSubQuotes();
        return $this;

    }

    /**
     * Adds quote id to session storage.
     *
     * @param int|string $subQuoteId
     * @return $this
     */
    protected function addSubQuoteId($subQuoteId)
    {
        $subQuoteIds = $this->getSubQuoteIds() ? $this->getSubQuoteIds() : [];
        $subQuoteIds[] = $subQuoteId;
        $this->storage->setSubQuoteIds($subQuoteIds);

        return $this;
    }

    /**
     * Returns list of subscription quotes.
     *
     * @return ModelQuote[]
     */
    public function getSubQuotes()
    {
        if ($this->quotes === null) {
            $quoteIds = $this->getSubQuoteIds();
            $this->quotes = [];
            if ($quoteIds) {
                $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                    ModelQuote::KEY_ENTITY_ID,
                    $quoteIds,
                    'in'
                )->create();
                $this->quotes = $this->quoteRepository->getList($searchCriteria)->getItems();
                $this->processQuote();
            }
        }

        return $this->quotes;
    }

    /**
     * Removes quote from subscription quotes list.
     *
     * @param ModelQuote $quote
     * @return $this
     */
    public function removeSubQuote(ModelQuote $quote)
    {
        $quoteId = $quote->getId();
        $this->removeSubQuoteId($quoteId);
        $this->quotes = array_filter(
            $this->getSubQuotes(),
            function ($subQuote) use ($quoteId) {
                return ($subQuote->getId() !== $quoteId);
            }
        );

        return $this;
    }

    /**
     * Removes all sub quotes
     *
     * @return $this
     */
    private function removeAllSubQuotes()
    {
        $this->quotes = null;
        return $this;
    }

    /**
     * Removes quote id from session storage.
     *
     * @param int $quoteId
     * @return $this
     */
    protected function removeSubQuoteId($quoteId)
    {
        $quoteIds = array_diff($this->getSubQuoteIds(), [$quoteId]);
        $this->storage->setSubQuoteIds($quoteIds);

        return $this;
    }

    /**
     * Returns first quote from subscription quotes list or false if list is empty.
     *
     * @return bool|ModelQuote
     */
    public function getFirstQuote()
    {
        $result = false;

        if (!empty($this->getSubQuotes())) {
            $result = reset($this->quotes);
        }

        return $result;
    }

    /**
     * Adds quote to subscription quote list.
     *
     * @param ModelQuote|int|string $quote
     * @return $this
     */
    public function addSubQuote($quote)
    {
        if (!$quote instanceof ModelQuote) {
            $quote = $this->quoteRepository->get($quote);
        }
        $this->addSubQuoteId($quote->getId());
        $needAddQuote = true;
        foreach ($this->quotes as $oldQuote) {
            if ($oldQuote->getId() == $quote->getId()) {
                $needAddQuote = false;
            }
        }
        if ($needAddQuote) {
            $this->quotes[] = $quote;
        }

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCreateNewCustomer($value)
    {
        $this->storage->setCreateNewCustomer($value);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreateNewCustomer()
    {
        return $this->storage->getCreateNewCustomer();
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCustomerEmail($value)
    {
        $this->storage->setCustomerEmail($value);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCustomerEmail()
    {
        return $this->storage->getCustomerEmail();
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCustomerGroup($value)
    {
        $this->storage->setCustomerGroup($value);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCustomerGroup()
    {
        return $this->storage->getCustomerGroup();
    }

    /**
     * @inheritdoc
     */
    public function clearStorage()
    {
        $this->quotes = [];
        return parent::clearStorage();
    }

    /**
     * Remove product inventory check for quote.
     *
     * @return void
     */
    protected function processQuote()
    {
        foreach ($this->quotes as $quote) {
            $quote->setIsSuperMode(true);
        }
    }
}
