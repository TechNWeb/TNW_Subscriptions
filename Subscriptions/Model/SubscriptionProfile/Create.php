<?php
namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;

class Create
{
    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;
    /** @var \Magento\Quote\Api\CartRepositoryInterface  */
    protected $cartRepository;
    /** @var \Magento\Quote\Model\QuoteManagement */
    protected $quoteManagement;
    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerFactory;
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $customerRepository;
    /** @var \Magento\Sales\Model\Service\OrderService */
    protected $orderService;
    /** @var BillingFrequencyRepository */
    protected $billingFrequencyRepository;

    /** @var  array */
    protected $billingFrequencies;
    /** @var \Magento\Quote\Model\Quote */
    protected $mainQuote;


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product $product,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        BillingFrequencyRepository $billingFrequencyRepository
    ) {
        $this->storeManager = $storeManager;
        $this->_product = $product;
        $this->cartRepository = $cartRepository;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     */
    public function createProfiles($quote)
    {
        $this->reset();
        $this->mainQuote = $quote;
        $profilesData = $this->populateProfilesData();

        foreach ($profilesData as $profile){
            $this->saveProfile($profile);
            $orderData = $this->getOrderDataForProfile($profile);
            $result = $this->createOrder($orderData);
        }
    }

    /**
     * @param array $orderData
     * @return array
     *
     */
    public function createOrder($orderData) {
        //create new quote
        $quote = $this->createNewCart();

        //add products to quote
        $this->addItemsInCart($orderData['items'], $quote);

        //set address to quote
        $quote->setShippingAddress(
            $this->getProfileShippingAddress()
        );

        $quote->setBillingAddress(
            $this->getProfileBillingAddress()
        );

        // Collect Rates and Set Shipping & Payment Method

        $shippingAddress=$quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($orderData['shipping_method']); //shipping method
        $quote->setPaymentMethod($orderData['payment_method']); //payment method
        $quote->setInventoryProcessed(false); //not effetc inventory
        $quote->save(); //Now Save quote and your quote is ready

        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => 'checkmo']);

        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        // Create Order From Quote
        $order = $this->quoteManagement->submit($quote);

        $increment_id = $order->getRealOrderId();
        if($increment_id){
            $result['order_id']= $increment_id;
        }else{
            $result=['error'=>1,'msg'=>'Your custom message'];
        }
        return $result;
    }

    /**
     * @return \Magento\Quote\Model\Quote
     */
    protected function createNewCart()
    {
        $customerId = $this->mainQuote->getCustomerId();
        $cartId = $this->quoteManagement->createEmptyCartForCustomer($customerId);

        /** @var \Magento\Quote\Model\Quote $cart */
        $cart = $this->cartRepository->get($cartId);
        $cart->setStoreId($this->mainQuote->getStoreId());

        return $cart;
    }

    /**
     * @param $orderData
     * @param \Magento\Quote\Model\Quote $quote
     */
    protected function addItemsInCart($orderData, $quote)
    {
        foreach ($orderData as $item) {
            $product = $this->_product->load($item['product_id']);
            $product->setPrice($item['price']);
            $quote->addProduct(
                $product,
                intval($item['qty'])
            );
        }
    }

    /**

     * @return array
     */
    protected function populateProfilesData()
    {
        $profilesData = $frequencies = [];
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($this->mainQuote->getAllItems() as $item){
            $frequency = $item->getBuyRequest()->getData('billing_frequency');

            if ($frequency){
                $profileUniqueKey = $this->getProfileUniqueKey($item, $frequency);

                $billingFrequency = $this->getBillingFrequency($frequency['billing_frequency_id']);

                if (!isset($profilesData[$profileUniqueKey])){
                    $profilesData[$profileUniqueKey] = [
                        'customer_id' => $this->mainQuote->getCustomerId(),
                        'billing_frequency_id' => $billingFrequency->getId(),
                        'label' => $billingFrequency->getLabel(),
                        'unit' => $billingFrequency->getUnit(),
                        'website_id' => $this->mainQuote->getStore()->getWebsiteId(),
                        'status' => 1, //TODO use Profile Status Source
                        'frequency' => $billingFrequency->getFrequency(),
                        'items' => [$item->getId()]
                    ];
                }else{
                    $profilesData[$profileUniqueKey]['items'][] = $item->getId();
                }
            }
        }

        return $profilesData;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item  $item
     * @param [] $frequency
     * @return string
     */
    protected function getProfileUniqueKey($item, $frequency)
    {
        //TODO add logic to use product trial period options
        $frequencyId = $frequency['billing_frequency_id'];
        $frequencyTerm = $frequency['term'];
        $frequencyStartDate = $frequency['start_on'];

        return $frequencyId .'_'. $frequencyTerm .'_'. $frequencyStartDate;
    }

    /**
     * @param $frequencyId
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
     */
    protected function getBillingFrequency($frequencyId)
    {
        if (!isset($this->billingFrequencies[$frequencyId])){
            $this->billingFrequencies[$frequencyId] = $this->billingFrequencyRepository->getById(
                $frequencyId
            );
        }

        return $this->billingFrequencies[$frequencyId];
    }

    protected function reset()
    {
        $this->billingFrequencies = null;
        $this->mainQuote = null;
    }

    protected function saveProfile($profile)
    {
        //TODO save Profile
        //TODO save Profile Products
    }

    /**
     * @return \Magento\Quote\Model\Quote\Address
     */
    protected function getProfileShippingAddress()
    {
        return $this->mainQuote->getShippingAddress();
    }

    /**
     * @return \Magento\Quote\Model\Quote\Address
     */
    protected function getProfileBillingAddress()
    {
        return $this->mainQuote->getShippingAddress();
    }

    protected function getOrderDataForProfile($profile)
    {
        //TODO create order data array
        $tempOrder = [
            'currency_id'  => 'USD',
            'customer_id'        => '1',
            'shipping_method' => [],
            'payment_method' => [],
            'items'=> [
                ['product_id'=>'1','qty'=>1],
                ['product_id'=>'2','qty'=>2]
            ]
        ];

        return $tempOrder;
    }
}