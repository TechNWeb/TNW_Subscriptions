<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;

class QuoteRepository implements \Magento\Quote\Api\CartRepositoryInterface
{
    /**
     * @var Quote[]
     */
    protected $quotesById = [];

    /**
     * @var Quote[]
     */
    protected $quotesByCustomerId = [];

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Quote\Api\Data\CartSearchResultsInterfaceFactory
     */
    protected $searchResultsDataFactory;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var \Magento\Quote\Model\QuoteRepository\SaveHandler
     */
    private $saveHandler;

    /**
     * @var \Magento\Quote\Model\QuoteRepository\LoadHandler
     */
    private $loadHandler;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * Constructor
     *
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Api\Data\CartSearchResultsInterfaceFactory $searchResultsDataFactory
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory
     * @param \Magento\Quote\Model\QuoteRepository\LoadHandler $loadHandler
     * @param \Magento\Quote\Model\QuoteRepository\SaveHandler $saveHandler
     */
    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Api\Data\CartSearchResultsInterfaceFactory $searchResultsDataFactory,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor,
        \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Magento\Quote\Model\QuoteRepository\LoadHandler $loadHandler,
        \Magento\Quote\Model\QuoteRepository\SaveHandler $saveHandler
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->searchResultsDataFactory = $searchResultsDataFactory;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->collectionProcessor = $collectionProcessor ;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->loadHandler = $loadHandler;
        $this->saveHandler = $saveHandler;
    }

    /**
     * @inheritdoc
     */
    public function get($cartId, array $sharedStoreIds = [])
    {
        if (!isset($this->quotesById[$cartId])) {
            $quote = $this->loadQuote('loadByIdWithoutStore', 'cartId', $cartId, $sharedStoreIds);
            $this->loadHandler->load($quote);
            $this->quotesById[$cartId] = $quote;
        }
        return $this->quotesById[$cartId];
    }

    /**
     * @inheritdoc
     */
    public function getForCustomer($customerId, array $sharedStoreIds = [])
    {
        if (!isset($this->quotesByCustomerId[$customerId])) {
            $quote = $this->loadQuote('loadByCustomer', 'customerId', $customerId, $sharedStoreIds);
            $this->loadHandler->load($quote);
            $this->quotesById[$quote->getId()] = $quote;
            $this->quotesByCustomerId[$customerId] = $quote;
        }

        return $this->quotesByCustomerId[$customerId];
    }

    /**
     * @inheritdoc
     */
    public function getActive($cartId, array $sharedStoreIds = [])
    {
        $quote = $this->get($cartId, $sharedStoreIds);
        if (!$quote->getIsActive()) {
            throw NoSuchEntityException::singleField('cartId', $cartId);
        }
        return $quote;
    }

    /**
     * @inheritdoc
     */
    public function getActiveForCustomer($customerId, array $sharedStoreIds = [])
    {
        $quote = $this->getForCustomer($customerId, $sharedStoreIds);
        if (!$quote->getIsActive()) {
            throw NoSuchEntityException::singleField('customerId', $customerId);
        }

        return $quote;
    }

    /**
     * @inheritdoc
     */
    public function save(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        if ($quote->getId()) {
            $currentQuote = $this->get($quote->getId(), [$quote->getStoreId()]);

            foreach ($currentQuote->getData() as $key => $value) {
                if (!$quote->hasData($key)) {
                    $quote->setData($key, $value);
                }
            }
        }

        $this->saveHandler->save($quote);
        unset($this->quotesById[$quote->getId()], $this->quotesByCustomerId[$quote->getCustomerId()]);
    }

    /**
     * @inheritdoc
     */
    public function delete(\Magento\Quote\Api\Data\CartInterface $quote)
    {
        $quoteId = $quote->getId();
        $customerId = $quote->getCustomerId();
        $quote->delete();
        unset($this->quotesById[$quoteId], $this->quotesByCustomerId[$customerId]);
    }

    /**
     * Load quote with different methods
     *
     * @param string $loadMethod
     * @param string $loadField
     * @param int $identifier
     * @param int[] $sharedStoreIds
     * @throws NoSuchEntityException
     * @return Quote
     */
    protected function loadQuote($loadMethod, $loadField, $identifier, array $sharedStoreIds = [])
    {
        /** @var Quote $quote */
        $quote = $this->quoteFactory->create();
        if ($sharedStoreIds) {
            $quote->setSharedStoreIds($sharedStoreIds);
        }

        $quote->$loadMethod($identifier)->setStoreId($this->storeManager->getStore()->getId());
        if (!$quote->getId()) {
            throw NoSuchEntityException::singleField($loadField, $identifier);
        }

        return $quote;
    }

    /**
     * @inheritdoc
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        /** @var \Magento\Quote\Api\Data\CartSearchResultsInterface $searchData */
        $searchData = $this->searchResultsDataFactory->create();
        $searchData->setSearchCriteria($searchCriteria);

        $this->collectionProcessor->process($searchCriteria, $quoteCollection);
        $this->extensionAttributesJoinProcessor->process($quoteCollection);
        foreach ($quoteCollection->getItems() as $quote) {
            /** @var CartInterface $quote */
            $this->loadHandler->load($quote);
        }

        $searchData->setItems($quoteCollection->getItems());
        $searchData->setTotalCount($quoteCollection->getSize());
        return $searchData;
    }
}
