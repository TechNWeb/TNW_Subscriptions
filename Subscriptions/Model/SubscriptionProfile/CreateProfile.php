<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Payment;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Address;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Customer;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Quote;
use TNW\Subscriptions\Model\SubscriptionProfile\Create as BaseCreate;
use TNW\Subscriptions\Model\Queue\Manager as QueueManager;

/**
 * Class for creating subscription profile.
 */
class CreateProfile extends BaseCreate
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
     * @var QuoteCreateInterface
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
     * Core event manager.
     *
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * Message history logger.
     *
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * Profile process queue manager.
     *
     * @var QueueManager
     */
    private $queueManager;

    /**
     * CreateProfile constructor.
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param Address $addressCreator
     * @param Quote $quoteCreator
     * @param Product $productModifier
     * @param Customer $customerCreator
     * @param Manager $profileManager
     * @param ManagerInterface $eventManager
     * @param MessageHistoryLogger $messageHistoryLogger
     * @param QueueManager $queueManager
     */
    public function __construct(
        Context $context,
        QuoteSessionInterface $session,
        Address $addressCreator,
        Quote $quoteCreator,
        Product $productModifier,
        Customer $customerCreator,
        Manager $profileManager,
        ManagerInterface $eventManager,
        MessageHistoryLogger $messageHistoryLogger,
        QueueManager $queueManager
    ) {
        $this->addressCreator = $addressCreator;
        $this->quoteCreator = $quoteCreator;
        $this->productModifier = $productModifier;
        $this->customerCreator = $customerCreator;
        $this->profileManager = $profileManager;
        $this->eventManager = $eventManager;
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->queueManager = $queueManager;

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
    public function setNeedCollect($needCollect)
    {
        $this->needCollect = $needCollect;
    }

    /**
     * Recollects and saves all subscription quotes if "needCollect" flag is set.
     */
    public function recollectSubscriptions()
    {
        if ($this->isNeedCollect()) {
            /** @var QuoteSessionInterface $session */
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
     * Removes product from subscription.
     *
     * @param array $request
     * @param string|int $quoteId
     * @param string|int $quoteItemId
     * @return array
     */
    public function removeSubscriptions(array $request, $quoteId, $quoteItemId)
    {
        /** @var ModelQuote $quote */
        $quote = $this->quoteCreator->getCartRepository()->get($quoteId);
        /** @var Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($item->getId() === $quoteItemId) {
                $request['product_id'] = $item->getProduct()->getId();
                $item->isDeleted(true);
                $this->quoteCreator->getCartRepository()->save($quote);
                if (!$quote->getAllItems()) {
                    $this->quoteCreator->getCartRepository()->delete($quote);
                    /** @var QuoteSessionInterface $session */
                    $session = $this->getSession();
                    $session->removeSubQuote($quote);
                }
                break;
            }
        }

        return $request;
    }

    /**
     * Returns new or already existing quote for adding in to it requested product.
     *
     * @return ModelQuote|null
     */
    private function getSubQuote()
    {
        $result = null;
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();
        $subQuotes = $session->getSubQuotes();
        /** @var ModelQuote $subQuote */
        foreach ($subQuotes as $subQuote) {
            if ($this->canAddProduct($subQuote)) {
                $result = $subQuote;
                break;
            }
        }
        $result = $result ?: $this->createSubCart();
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
     * @return ModelQuote
     */
    public function createSubCart()
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
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();
        $subQuotes = $session->getSubQuotes();

        foreach ($subQuotes as $subQuote) {
            if ($subQuote->isVirtual()) {
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
     * Set payment data into subscription quotes.
     *
     * @param [] $data
     * @return array
     */
    public function setPaymentData($data)
    {
        $result = [];

        try {
            /** @var QuoteSessionInterface $session */
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
     * Sets payment method in to subscription quotes.
     *
     * @param $method
     * @return array
     */
    public function setPaymentMethod($method)
    {
        $result = [];
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();
        $subQuotes = $session->getSubQuotes();
        try {
            foreach ($subQuotes as $subQuote) {
                $subQuote->getPayment()->setMethod($method);
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
     * @return bool|Payment
     */
    public function getPayment()
    {
        /** @var QuoteSessionInterface $session */
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
            /** @var QuoteSessionInterface $session */
            $session = $this->getSession();
            $subQuotes = $session->getSubQuotes();
            $basicPayment = $session->getFirstQuote()->getPayment();
            /** @var ModelQuote $subQuote */
            foreach ($subQuotes as $subQuote) {
                $subQuote->setCustomer($customer);
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
                //Create new profile
                $profile = $this->createProfile($subQuote, $basicPayment);
                //Add comment about profile creating
                $this->logToMessageCreateSubscription($profile);
                //Assign quote to new profile
                $relation = $this->profileManager->assignQuoteToProfile($subQuote, $profile);
                //Add new relation to profile processing queue in "running" state.
                $queueItemIds = $this->queueManager->insertItems(
                    [$relation->getId()],
                    true
                );
                try {
                    $order = $this->profileManager->processProfile($subQuote);
                } catch (\Exception $e) {
                    $this->getContext()->getMessageManager()->addError(
                        __('Unable to process order for profile ') . $profile->getId()
                    );
                    $this->getContext()->log($e->getMessage());
                    $this->profileManager->setPastDueStatus()->saveProfile();
                    $this->queueManager->makeError($queueItemIds, $e->getMessage());
                }
                if (isset($order)) {
                    $this->profileManager->assignOrderToProfile($relation, $order);
                    $this->logMessageOrderCreated(
                        $profile->getId(),
                        $order->getId(),
                        $subQuote->getId()
                    );
                    $this->profileManager->setActiveStatus()->saveProfile();
                    $this->queueManager->makeCompleted($queueItemIds);
                    $this->eventManager->dispatch(
                        'checkout_submit_all_after',
                        ['order' => $order, 'quote' => $subQuote]
                    );
                }
                $profiles[] = $profile;
                //TODO add here email sending
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
     * @param ModelQuote $subQuote
     * @param $payment
     * @return SubscriptionProfileInterface
     */
    private function createProfile(
        ModelQuote $subQuote,
        $payment
    ) {
        $profile = $this->profileManager->reset()
            ->populateProfileData($subQuote)
            ->populatePaymentData($payment)
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
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();
        $subQuotes = $session->getSubQuotes();

        $needRecollect = false;

        foreach ($subQuotes as $subQuote) {
            if (!$subQuote->getQuoteCurrencyCode() || $subQuote->getQuoteCurrencyCode() != $currencyCode) {
                $subQuote->setQuoteCurrencyCode($currencyCode);
                $needRecollect = true;
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
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();
        $quotes = $session->getSubQuotes();

        if (!empty($quotes)) {
            foreach ($quotes as $quote) {
                $grandTotal += $quote->getGrandTotal() * 1;
            }
        }

        return $grandTotal;
    }

    /**
     * Reassign quotes by customer id.
     *
     * @param int $customerId
     */
    public function reassignQuote($customerId)
    {
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();

        $customer = $this->customerCreator->getCustomer($customerId);

        foreach ($session->getSubQuotes() as $quote) {
            $quote->assignCustomer($customer);
            $this->quoteCreator->getCartRepository()->save($quote);
        }
    }

    /**
     * Change customer id value in session.
     *
     * @param int $customerId
     * @return void
     */
    public function changeCustomerIdInSession($customerId)
    {
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();
        $session->setCustomerId($customerId);
    }

    /**
     * If customer on the first step is changed we need to change customer in all quotes.
     */
    public function changeCustomerInQuote()
    {
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();
        $sessionCustomerId = $session->getCustomerId();
        $customer = null;
        if ($sessionCustomerId) {
            $customer = $this->customerCreator->getCustomer($sessionCustomerId);
        }

        /** @var array $subQuotes */
        $subQuotes = $session->getSubQuotes();

        /** @var ModelQuote $subQuote */
        foreach ($subQuotes as $subQuote) {
            $subQuoteCustomerId = $subQuote->getCustomerId();

            if ($sessionCustomerId != $subQuoteCustomerId) {
                $defaultShippingId = null;
                $defaultBillingId = null;
                $customerEmail = null;

                //If there is a customer we find customer's default addresses
                if ($customer) {
                    $defaultShippingId = $customer->getDefaultShipping();
                    $defaultBillingId = $customer->getDefaultBilling();
                    $customerEmail = $customer->getEmail();
                }
                //If there is default shipping address we load it. Otherwise we get empty address.
                if ($defaultShippingId) {
                    /** @var QuoteAddress $shippingAddress */
                    $shippingAddress = $customer->getAddressById($defaultShippingId);
                } else {
                    /** @var QuoteAddress $shippingAddress */
                    $shippingAddress = $this->addressCreator->getEmptyAddressObject($customerEmail);
                }
                //If there is default billing address we load it. Otherwise we get empty address.
                if ($defaultBillingId) {
                    /** @var QuoteAddress $billingAddress */
                    $billingAddress = $customer->getAddressById($defaultBillingId);
                } else {
                    /** @var QuoteAddress $billingAddress */
                    $billingAddress = $this->addressCreator->getEmptyAddressObject($customerEmail);
                }
                //If there is existing customer we assign his to the quote.
                //Otherewise we assign empty customer and empty addresses.
                if ($customer) {
                    $subQuote->assignCustomerWithAddressChange($customer, $billingAddress, $shippingAddress);
                } else {
                    $customerDataObject = $this->customerCreator->getCustomer();
                    $subQuote->setBillingAddress($billingAddress);
                    $subQuote->setShippingAddress($shippingAddress);
                    $subQuote->setCustomer($customerDataObject);
                }

                $subQuote->getShippingAddress()->setCollectShippingRates(true);
                $this->setNeedCollect(true);
            } else {
                break;
            }
        }
    }

    /**
     * Cleares extra data on account step (ex. customer_address_id from quote address if it is exist).
     *
     * @return void
     */
    public function clearAccountStepData()
    {
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();
        /** @var array $subQuotes */
        $subQuotes = $session->getSubQuotes();

        foreach ($subQuotes as $subQuote) {
            $quoteAddresses = $subQuote->getAddressesCollection();
            $this->clearCustomerAddressId($quoteAddresses);
            $this->setNeedCollect(true);
        }
    }

    /**
     * Cleares extra data on payment and billing step
     * (ex. customer_address_id, shipping method).
     *
     * @return void
     */
    public function clearBillingStepData()
    {
        /** @var QuoteSessionInterface $session */
        $session = $this->getSession();
        /** @var array $subQuotes */
        $subQuotes = $session->getSubQuotes();

        /** @var ModelQuote $subQuote */
        foreach ($subQuotes as $subQuote) {
            $this->clearCustomerAddressId([$subQuote->getBillingAddress()]);
            $subQuote->getShippingAddress()->setShippingMethod('')->setShippingDescription('');
            $subQuote->getShippingAddress()->setCollectShippingRates(true);
            $this->setNeedCollect(true);
        }
    }

    /**
     * Cleares customer_address_id fro quote addresses.
     *
     * @param array $quoteAddresses
     * @return void
     */
    private function clearCustomerAddressId($quoteAddresses)
    {
        foreach ($quoteAddresses as $quoteAddress) {
            $quoteAddress->setCustomerAddressId(null);
        }
    }

    /**
     * Log message for Subscription Profile creation.
     *
     * @param SubscriptionProfileInterface $profile
     *
     * @return void
     */
    private function logToMessageCreateSubscription(SubscriptionProfileInterface $profile)
    {
        $message = sprintf(
            $this->messageHistoryLogger->getMessage(MessageHistoryLogger::MESSAGE_SUBSCRIPTION_CREATED),
            $profile->getLabel()
        );

        $this->messageHistoryLogger->log(
            $message,
            $profile->getId()
        );
    }

    /**
     * Log message for Subscription Profile order creating from quote.
     *
     * @param int $profileId
     * @param int $orderId
     * @param int $quoteId
     *
     * @return void
     */
    private function logMessageOrderCreated($profileId, $orderId, $quoteId)
    {
        $message = sprintf(
            $this->messageHistoryLogger->getMessage(MessageHistoryLogger::MESSAGE_ORDER_CREATED_FROM_QUOTE),
            $this->messageHistoryLogger->getOrderIncrementIdById($orderId),
            $this->messageHistoryLogger->getConvertedQuoteId($quoteId)
        );

        $this->messageHistoryLogger->log(
            $message,
            $profileId
        );
    }
}
