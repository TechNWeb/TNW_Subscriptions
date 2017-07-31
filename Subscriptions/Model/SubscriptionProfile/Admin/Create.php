<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Backend\Session\Quote as Session;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Address;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Customer;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Quote;
use TNW\Subscriptions\Model\SubscriptionProfile\Create as BaseCreate;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;

/**
 * Class for creating subscriptions in admin.
 */
class Create extends BaseCreate
{
    /**
     * Quote address creator.
     *
     * @var Address
     */
    private $addressCreator;

    /**
     * Quote creator.
     *
     * @var Quote
     */
    private $quoteCreator;

    /**
     * Product modifier.
     *
     * @var Product
     */
    private $productModifier;

    /**
     * Recollect and save quotes flag.
     *
     * @var bool
     */
    private $needCollect;

    /**
     * Subscription profile manager.
     *
     * @var Manager
     */
    private $profileManager;

    /**
     * Customer creator.
     *
     * @var Customer
     */
    private $customerCreator;

    /**
     * @var CartManagementInterface
     */
    private $quoteManagement;

    /**
     * Core event manager.
     *
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * Create constructor.
     * @param Context $context
     * @param SessionManagerInterface $session
     * @param Address $addressCreator
     * @param Quote $quoteCreator
     * @param Product $productModifier
     * @param Customer $customerCreator
     * @param Manager $profileManager
     * @param CartManagementInterface $quoteManagement
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Context $context,
        SessionManagerInterface $session,
        Address $addressCreator,
        Quote $quoteCreator,
        Product $productModifier,
        Customer $customerCreator,
        Manager $profileManager,
        CartManagementInterface $quoteManagement,
        ManagerInterface $eventManager
    ) {
        $this->addressCreator = $addressCreator;
        $this->quoteCreator = $quoteCreator;
        $this->productModifier = $productModifier;
        $this->customerCreator = $customerCreator;
        $this->profileManager = $profileManager;
        $this->quoteManagement = $quoteManagement;
        $this->eventManager = $eventManager;
        parent::__construct($context, $session);
    }


    /**
     * @return bool
     */
    public function isNeedCollect()
    {
        return $this->needCollect;
    }

    /**
     * @param bool $needCollect
     */
    public function setNeedCollect(bool $needCollect)
    {
        $this->needCollect = $needCollect;
    }

    /**
     * Recollects and saves all subscription quotes if "needCollect" flag is set.
     */
    public function recollectSubscriptions()
    {
        if ($this->isNeedCollect()) {
            /** @var Session $session */
            $session = $this->getSession();
            foreach ($session->getSubQuotes() as $subQuote) {
                $subQuote->collectTotals();
                $this->quoteCreator->getCartRepository()->save($subQuote);
            }
        }
    }

    /**
     * Adds product into new or already existing subscription quote.
     *
     * @param [] $productData
     * @return array
     */
    public function addToSubscription($productData)
    {
        $this->productModifier->reset();

        $result = [];
        try {
            $this->productModifier->setData($productData);
            $product = $this->productModifier->getPreparedProduct();

            $quote = $this->getSubQuote();
            $quote->addProduct(
                $product,
                $this->productModifier->getPreparedBuyRequest()
            );
            $quote->setTotalsCollectedFlag(false);
            $this->quoteCreator->getCartRepository()->save($quote);

            $result['error'] = false;
        } catch (\Exception $e) {
            $this->getContext()->log($e->getMessage());
            $result = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        return $result;
    }

    /**
     * Returns new or already existing quote for adding in to it requested product.
     *
     * @return ModelQuote|null
     */
    private function getSubQuote()
    {
        $result = null;
        /** @var Session $session */
        $session = $this->getSession();
        $subQuotes = $session->getSubQuotes();
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

        $session->addSubQuote($result);

        return $result;
    }

    /**
     * Checks whether it is possible to add a product in to quote.
     *
     * @param ModelQuote $subQuote
     * @return bool
     */
    private function canAddProduct(ModelQuote $subQuote)
    {
        $result = false;

        $quoteItems = $subQuote->getAllItems();
        /** @var Item $item */
        $item = $quoteItems ? reset($quoteItems) : null;

        if ($item) {
            $request = $item->getBuyRequest()
                ->getDataByPath(static::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME . DIRECTORY_SEPARATOR . self::UNIQUE);

            $newRequest = $this->productModifier->getPreparedBuyRequest()
                ->getDataByPath(static::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME . DIRECTORY_SEPARATOR . self::UNIQUE);

            if ($request == $newRequest) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Creates empty quote and assigns customer if there is a customer id in session.
     *
     * @return int|string
     */
    private function createSubCart()
    {
        return $this->quoteCreator->createSubCart();
    }

    /**
     * Validates and sets shipping address to all subscription quotes.
     *
     * @param [] $address
     * @param null $customerAddressId
     * @return array
     */
    public function setShippingAddress($address, $customerAddressId = null)
    {
        $result = $this->addressCreator->setAddress(
            $address,
            QuoteAddress::TYPE_SHIPPING,
            $customerAddressId
        );

        if ($result === true) {
            $this->setNeedCollect(true);
        }

        return $result;
    }

    /**
     * Validates and sets billing address to all subscription quotes.
     *
     * @param [] $address
     * @param null $customerAddressId
     * @return array|bool
     */
    public function setBillingAddress($address, $customerAddressId = null)
    {
        $result = $this->addressCreator->setAddress(
            $address,
            QuoteAddress::TYPE_BILLING,
            $customerAddressId
        );

        if ($result === true) {
            $this->setNeedCollect(true);
        }

        return $result;
    }

    /**
     * Returns shipping address from the first subscription quote or empty address object.
     *
     * @return QuoteAddress
     */
    public function getShippingAddress()
    {
        return $this->addressCreator->getAddress(QuoteAddress::TYPE_SHIPPING);
    }

    /**
     * Returns billing address from the first subscription quote or empty address object.
     *
     * @return QuoteAddress
     */
    public function getBillingAddress()
    {
        return $this->addressCreator->getAddress(QuoteAddress::TYPE_BILLING);
    }

    /**
     * Sets into subscription quotes shipping methods.
     *
     * @param $methods
     * @return array
     */
    public function setShippingMethods($methods)
    {
        $result = [];
        /** @var Session $session */
        $session = $this->getSession();
        $subQuotes = $session->getSubQuotes();

        foreach ($subQuotes as $subQuote) {
            if ($subQuote->isVirtual()){
                continue;
            }

            /** @var string|null $method */
            $method = !empty($methods[$subQuote->getId()]) ? $methods[$subQuote->getId()] : null;

            if ($method) {
                try {
                    $subQuote->getShippingAddress()->setShippingMethod($method);
                    $subQuote->getShippingAddress()->setCollectShippingRates(true);
                    $this->setNeedCollect(true);
                } catch (\Exception $e) {
                    $this->getContext()->log($e->getMessage());

                    $result[] = __(
                        'Can not set shipping method - ' . $method . ' to quote with id - ' . $subQuote->getId()
                    );
                }

            } else {
                $result[] = __("Shipping method is required and can't be empty.");
            }
        }

        return $result;
    }

    /**
     * Set payment into subscription quotes.
     *
     * @param string $method
     * @param [] $data
     * @return array
     */
    public function setPayment($method, $data)
    {
        $result = [];

        try {
            $data['method'] = $method;
            /** @var Session $session */
            $session = $this->getSession();
            $subQuotes = $session->getSubQuotes();

            foreach ($subQuotes as $subQuote) {
                $subQuote->getPayment()->importData($data);
            }
            $this->setNeedCollect(true);
        } catch (\Exception $e) {
            $result[] = __('Payment: ') . $e->getMessage();
            $this->getContext()->log($e->getMessage());
        }

        return $result;
    }

    /**
     * Returns Payment from the first subscription quote or false if there is no quotes.
     *
     * @return bool|ModelQuote\Payment
     */
    public function getPayment()
    {
        /** @var Session $session */
        $session = $this->getSession();
        $quotes = $session->getSubQuotes();

        if (!empty($quotes)) {
            $quote = reset($quotes);

            $result = $quote->getPayment();
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Creates subscription profiles.
     *
     * @return SubscriptionProfileInterface[]
     */
    public function createSubscriptions()
    {
        $profiles = [];

        try {
            $customer = $this->customerCreator->prepareCustomer();
            /** @var Session $session */
            $session = $this->getSession();
            $subQuotes = $session->getSubQuotes();

            foreach ($subQuotes as $subQuote) {
                $this->quoteCreator->fillCustomerData($customer);
                $errors = $this->quoteCreator->validate($subQuote);

                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        $this->getContext()->log($error);
                        $this->getContext()->getMessageManager()->addError($error);
                    }
                    //Maybe we need to delete customer in this case.
                    throw new \Exception(__('Quote validation is failed.'));
                }

                $profile = $this->createProfile($subQuote);

                if ($profile) {
                    $order = $this->quoteManagement->submit($subQuote);
                    $this->profileManager->assignOrderToProfile($order, $profile);
                    $this->eventManager->dispatch(
                        'checkout_submit_all_after',
                        ['order' => $order, 'quote' => $subQuote]
                    );
                    $profiles[] = $profile;
                    //TODO add here email sending
                }
            }
        } catch (\Exception $e) {
            $this->getContext()->log($e->getMessage());
            $this->getContext()->getMessageManager()->addError($e->getMessage());
        }

        return $profiles;
    }

    /**
     * Creates subscription profile.
     *
     * @param $subQuote
     * @return SubscriptionProfileInterface
     */
    private function createProfile($subQuote)
    {
        $profile = $this->profileManager->reset()
            ->populateProfileData($subQuote)
            ->saveProfile();

        return $profile;
    }

    /**
     * Set selected currency to all subscription quotes
     * and start recollect quotes.
     *
     * @param $currencyCode
     */
    public function setCurrency($currencyCode)
    {
        /** @var Session $session */
        $session = $this->getSession();
        $subQuotes = $session->getSubQuotes();

        $needRecollect = false;

        foreach ($subQuotes as $subQuote) {
            if (!$subQuote->getQuoteCurrencyCode() || $subQuote->getQuoteCurrencyCode() != $currencyCode) {
                $subQuote->setQuoteCurrencyCode($currencyCode);
                $needRecollect= true;
            }
        }

        if ($needRecollect) {
            $this->setNeedCollect(true);
        }
    }

    /**
     * Returns SubQuotes grand total.
     *
     * @return int
     */
    public function getSubQuotesGrandTotal()
    {
        $grandTotal = 0;
        /** @var Session $session */
        $session = $this->getSession();
        $quotes = $session->getSubQuotes();

        if (!empty($quotes)) {
            foreach ($quotes as $quote) {
                $grandTotal += $quote->getGrandTotal() * 1;
            }
        }

        return $grandTotal;
    }
}
