<?php

namespace TNW\Subscriptions\Model\Backend\Session;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\GroupManagementInterface;
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
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote as ModelQuote;

/**
 * Class Quote
 *
 * @method Quote setCustomerId($id)
 * @method int getCustomerId()
 * @method int getStoreId()
 * @method Quote setCurrencyId($currencyId)
 * @method int getCurrencyId()
 * @package TNW\Subscriptions\Model\Backend\Session
 */
class Quote extends SessionManager
{
    /** @var ModelQuote[] */
    protected $quotes;

    /** @var Store */
    protected $store;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var CartRepositoryInterface */
    protected $quoteRepository;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /** @var GroupManagementInterface */
    protected $groupManagement;

    /** @var QuoteFactory */
    protected $quoteFactory;

    /**
     * Quote constructor.
     * @param Http $request
     * @param SidResolverInterface $sidResolver
     * @param ConfigInterface $sessionConfig
     * @param SaveHandlerInterface $saveHandler
     * @param ValidatorInterface $validator
     * @param StorageInterface $storage
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param State $appState
     * @param CustomerRepositoryInterface $customerRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param StoreManagerInterface $storeManager
     * @param GroupManagementInterface $groupManagement
     * @param QuoteFactory $quoteFactory
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
        CustomerRepositoryInterface $customerRepository,
        CartRepositoryInterface $quoteRepository,
        StoreManagerInterface $storeManager,
        GroupManagementInterface $groupManagement,
        QuoteFactory $quoteFactory
    ) {
        $this->customerRepository = $customerRepository;
        $this->quoteRepository = $quoteRepository;
        $this->storeManager = $storeManager;
        $this->groupManagement = $groupManagement;
        $this->quoteFactory = $quoteFactory;
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
     * @return []|null
     */
    public function getSubQuoteIds()
    {
        return $this->storage->getSubQuoteIds();
    }

    /**
     * @param int|string $subQuoteId
     * @return $this
     */
    protected function addSubQuoteId($subQuoteId)
    {
        $subQuoteIds = $this->getSubQuoteIds() ? $this->getSubQuoteIds() : [];

        array_push($subQuoteIds, $subQuoteId);

        $this->storage->setSubQuoteIds($subQuoteIds);

        return $this;
    }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface[]|ModelQuote[]
     */
    public function getSubQuotes()
    {
        if ($this->quotes === null){
            //TODO
            $quoteIds = $this->getSubQuoteIds();
            $this->quotes = $this->quoteRepository->getList()->getItems();
        }

        return $this->quotes;
    }

    /**
     * @param ModelQuote|int|string $quote
     * @return $this
     */
    public function addSubQuote($quote)
    {
        if (!$quote instanceof ModelQuote){
            $quote = $this->quoteRepository->get($quote);
        }

        $this->addSubQuoteId($quote->getId());
        $this->quotes[] = $quote;

        return $this;
    }

    /**
     * @return []|null
     */
    public function getBillingAddressData()
    {
        return $this->storage->getSubBillingAddressData();
    }

    /**
     * @param [] $data
     * @return $this
     */
    public function setBillingAddressData($data)
    {
        $this->storage->setSubBillingAddressData($data);

        return $this;
    }

    public function getShippingAddressData()
    {
        return $this->storage->getSubShippingAddressData();
    }

    /**
     * @param [] $data
     * @return $this
     */
    public function setShippingAddressData($data)
    {
        $this->storage->setSubShippingAddressData($data);

        return $this;
    }


    public function clearStorage()
    {
        return parent::clearStorage();
    }
}