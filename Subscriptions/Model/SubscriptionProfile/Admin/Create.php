<?php

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\QuoteFactory as ModelQuoteFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Backend\Session\Quote;
use TNW\Subscriptions\Model\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Quote\Model\Quote\AddressFactory;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\Trial;

class Create
{
    const SUBSCRIPTION_BUY_REQUEST_PARAM_NAME = 'subscription_data';

    /** @var Context */
    protected $context;

    /** @var Quote */
    protected $session;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    protected $cartRepository;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var DataObject */
    protected $buyRequest;

    /** @var ModelQuoteFactory */
    protected $quoteFactory;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var GroupManagementInterface */
    protected $groupManagement;

    /** @var AddressFactory */
    protected $addressFactory;

    /**
     * Create constructor.
     * @param Context $context
     * @param Quote $session
     * @param StoreManagerInterface $storeManager
     * @param CartRepositoryInterface $cartRepository
     * @param ModelQuoteFactory $quoteFactory
     * @param AddressFactory $addressFactory
     * @param GroupManagementInterface $groupManagement
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        Quote $session,
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $cartRepository,
        ModelQuoteFactory $quoteFactory,
        AddressFactory $addressFactory,
        GroupManagementInterface $groupManagement,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->context = $context;
        $this->session = $session;
        $this->storeManager = $storeManager;
        $this->cartRepository = $cartRepository;
        $this->quoteFactory = $quoteFactory;
        $this->addressFactory = $addressFactory;
        $this->groupManagement = $groupManagement;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @return DataObject
     */
    public function getBuyRequest()
    {
        return $this->buyRequest;
    }

    /**
     * @param [] $productData
     * @return array
     */
    public function addToSubscription($productData)
    {
        $this->buyRequest = null;

        $result = [];
        try {
            $product = $this->prepareProduct($productData);

            $this->prepareBuyRequest($productData);

            $quote = $this->getSubQuote();

            $quote->addProduct($product, $this->getBuyRequest());
            $quote->setTotalsCollectedFlag(false);
            $this->cartRepository->save($quote);

            $result['error'] = false;
        } catch (\Exception $e) {
            $this->context->log($e->getMessage());
            $result = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        return $result;
    }

    /**
     * @param $productId
     * @return ProductInterface
     */
    protected function getProduct($productId)
    {
        return $this->productRepository->getById($productId);
    }

    /**
     * @return ModelQuote|null
     */
    protected function getSubQuote()
    {
        $result = null;

        $subQuotes = $this->session->getSubQuotes();

        $canAdd = false;
        /** @var ModelQuote $subQuote */
        foreach ($subQuotes as $subQuote) {
            if ($this->canAddProduct($subQuote)) {
                $canAdd = true;
                $result = $subQuote;
                break;
            }
        }

        if (!$canAdd) {
            $result = $this->createSubCart();
        }

        $this->session->addSubQuote($result);

        return $result;
    }

    /**
     * @param ModelQuote $subQuote
     * @return bool
     */
    protected function canAddProduct($subQuote)
    {
        $result = false;

        $quoteItems = $subQuote->getAllItems();
        /** @var Item $item */
        $item = $quoteItems ? reset($quoteItems) : null;

        if ($item) {
            $request = $item->getBuyRequest()->getData(self::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);

            $newRequest = $this->buyRequest->getData(self::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);

            if ($request == $newRequest) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @param [] $productData
     */
    protected function prepareBuyRequest($productData)
    {
        /** @var Product $product */
        $product = $this->getProduct($productData['product_id']);

        //Note: If product "is trial" then "start on" is start date of trial period,
        // otherwise "start on" is start date of subscription
        $data = [
            'qty' => $productData['qty'],
            self::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                'billing_frequency' => $productData['product_billing_frequency'],
                'term' => $productData['term'],
                'period' => $productData['period'],
                'is_trial' => $product->getData(Trial::CODE_TRIAL) ? true : false,
                'start_on' => $this->getStartOnDate($productData['start_on']),
            ],
        ];

        $this->buyRequest = new DataObject($data);
    }

    /**
     * @param $productData
     * @return ProductInterface
     */
    protected function prepareProduct($productData)
    {
        $product = $this->getProduct($productData['product_id']);

        $product->setPrice($productData['price']);

        return $product;
    }

    /**
     * @return int|string
     */
    protected function createSubCart()
    {
        /** @var ModelQuote $quote */
        $quote = $this->quoteFactory->create();

        if ($this->session->getStoreId()) {
            $quote->setCustomerGroupId($this->groupManagement->getDefaultGroup()->getId());
            $quote->setIsActive(false);
            $quote->setStoreId($this->session->getStoreId());

            if (!$this->session->getCustomerId()){
                $quote->setBillingAddress($this->addressFactory->create());
                $quote->setShippingAddress($this->addressFactory->create());
            }

            $this->cartRepository->save($quote);
            $quote = $this->cartRepository->get($quote->getId(), [$this->session->getStoreId()]);

            if ($this->session->getCustomerId() &&
                $this->session->getCustomerId() != $quote->getCustomerId()
            ) {
                $customer = $this->customerRepository->getById($this->session->getCustomerId());
                $quote->assignCustomer($customer);
                $quote->setTotalsCollectedFlag(true);
                $this->cartRepository->save($quote);
            }
        }

        $quote->setIgnoreOldQty(true);
        $quote->setIsSuperMode(true);

        return $quote;
    }

    /**
     * @param $startOn
     * @return mixed
     */
    protected function getStartOnDate($startOn)
    {
        switch ($startOn){
            case StartDateType::LAST_DAY_OF_THE_CURRENT_MONTH:
                $result = new \DateTime();
                $result = $result->format('Y-m-t');
                break;
            case StartDateType::MOMENT_OF_PURCHASE:
                $result = new \DateTime();
                $result = $result->format('Y-m-d');
                break;
            default:
                $result = new \DateTime($startOn);
                $result = $result->format('Y-m-d');
                break;
        }

        return $result;
    }
}