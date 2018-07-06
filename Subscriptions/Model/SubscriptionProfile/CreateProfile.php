<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Payment;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Cron\Quote\Creator as QuoteGenerator;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute as SubscriptionAttributes;
use TNW\Subscriptions\Model\Queue\Manager as QueueManager;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Address;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Customer;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product;
use TNW\Subscriptions\Model\SubscriptionProfile\Create as BaseCreate;

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
     * Profile future orders generator.
     *
     * @var QuoteGenerator
     */
    private $quoteGenerator;

    /**
     * Subscription quotes.
     *
     * @var array
     */
    private $subQuotes;

    /**
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param Address $addressCreator
     * @param QuoteCreateInterface $quoteCreator
     * @param Product $productModifier
     * @param Customer $customerCreator
     * @param Manager $profileManager
     * @param ManagerInterface $eventManager
     * @param MessageHistoryLogger $messageHistoryLogger
     * @param QueueManager $queueManager
     * @param QuoteGenerator $quoteGenerator
     */
    public function __construct(
        Context $context,
        QuoteSessionInterface $session,
        Address $addressCreator,
        QuoteCreateInterface $quoteCreator,
        Product $productModifier,
        Customer $customerCreator,
        Manager $profileManager,
        ManagerInterface $eventManager,
        MessageHistoryLogger $messageHistoryLogger,
        QueueManager $queueManager,
        QuoteGenerator $quoteGenerator
    ) {
        $this->addressCreator = $addressCreator;
        $this->quoteCreator = $quoteCreator;
        $this->productModifier = $productModifier;
        $this->customerCreator = $customerCreator;
        $this->profileManager = $profileManager;
        $this->eventManager = $eventManager;
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->queueManager = $queueManager;
        $this->quoteGenerator = $quoteGenerator;

        parent::__construct($context, $session);
    }

    /**
     * @return bool
     */
    protected function isNeedCollect()
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
     * Gets subscription quotes.
     *
     * @return array
     */
    public function getSubQuotes()
    {
        if (!$this->subQuotes){
            $this->subQuotes = $this->getSession()->getSubQuotes();
        }

        return $this->subQuotes;
    }

    /**
     * Sets subscription quotes.
     *
     * @param array $subQuotes
     * @return void
     */
    public function setSubQuotes(array $subQuotes)
    {
        $this->subQuotes = $subQuotes;
    }

    /**
     * Recollects and saves all subscription quotes if "needCollect" flag is set.
     */
    public function recollectSubscriptions()
    {
        if ($this->isNeedCollect()) {
            foreach ($this->getSubQuotes() as $subQuote) {
                $subQuote->collectTotals();
                $this->quoteCreator->getCartRepository()->save($subQuote);
            }
        }
    }

    /**
     * Adds product into new or already existing subscription quote.
     *
     * @param array $productData
     * @return false|Item
     */
    public function addToSubscription(array $productData)
    {
        $this->productModifier->reset();
        $result = false;
        $this->setSubQuotes($this->getSession()->getSubQuotes());
        $this->productModifier->setData($productData);
        $product = $this->productModifier->getProduct();
        $quote = $this->getSubQuote();
        if ($this->canUpdateItemQty($quote, $product)) {
            $item = $quote->addProduct(
                $product,
                $this->productModifier->getPreparedBuyRequest()
            );
            if ($item instanceof Item) {
                $this->productModifier->setInitialFeeToItem($item);
                $quote->setTotalsCollectedFlag(false);
                $quote->getShippingAddress()->setCollectShippingRates(true);
                $this->quoteCreator->getCartRepository()->save($quote);
                $result = $item;
            } else {
                $this->getContext()->throwException($item);
            }
        } else {
            $result = $quote->getItemByProduct($product);
        }

        return $result;
    }

    /**
     * Check if quote item qty can be updated.
     * Depends on:
     * 1. product attribute 'tnw_subscr_unlock_preset_qty' value;
     * 2. quote item existence.
     *
     * @param ModelQuote $quote
     * @param MagentoProduct $product
     * @return bool
     */
    private function canUpdateItemQty(ModelQuote $quote, MagentoProduct $product)
    {
        $productPresetQty = $product->getData(SubscriptionAttributes::SUBSCRIPTION_UNLOCK_PRESET_QTY);
        $quoteItem = $quote->getItemByProduct($product);

        return !($productPresetQty && $quoteItem);
    }

    /**
     * Recollects unmodified quotes
     */
    public function recollectUnmodifiedQuotes()
    {
        foreach ($this->getSubQuotes() as $subQuote) {
            if (!$subQuote->getIsModified()) {
                $subQuote->setTotalsCollectedFlag(false);
                $this->quoteCreator->getCartRepository()->save($subQuote);
            }
        }
    }

    /**
     * Removes product from subscription (quoteItem from parent quote).
     * It also recalculate parent quote shipping rates.
     * If $quoteItem is the last item in parent quote whole quote will be removed.
     *
     * @param Item $quoteItem
     * @return ModelQuote|bool Returns parent quote for $quoteItem or false if quote was removed.
     */
    public function removeSubscriptions(Item $quoteItem)
    {
        /** @var ModelQuote $quote */
        $quote = $quoteItem->getQuote();
        $quoteItem->isDeleted(true);
        if (!$quote->getAllVisibleItems()) {
            $this->quoteCreator->getCartRepository()->delete($quote);
            $result = false;
        } else {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $this->quoteCreator->getCartRepository()->save($quote);
            $result = $quote;
        }

        return $result;
    }

    /**
     * Returns new or already existing quote for adding in to it requested product.
     *
     * @return ModelQuote
     */
    private function getSubQuote()
    {
        $result = null;
        $subQuotes = $this->getSubQuotes();
        /** @var ModelQuote $subQuote */
        foreach ($subQuotes as $subQuote) {
            if ($this->canAddProduct($subQuote)) {
                $result = $subQuote;
                break;
            }
        }
        $result = $result ?: $this->createSubCart();

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

        $quoteItems = $subQuote->getAllVisibleItems();
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
    protected function createSubCart()
    {
        return $this->quoteCreator->createSubCart();
    }

    /**
     * Validates and sets shipping address to all subscription quotes.
     *
     * @param [] $address
     * @param null $customerAddressId
     * @return array|bool
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
     * @param array $methods
     * @return array
     */
    public function setShippingMethods(array $methods)
    {
        $result = [];
        foreach ($this->getSubQuotes() as $subQuote) {
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
            foreach ($this->getSubQuotes() as $subQuote) {
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
     * @param string $method
     * @return array
     */
    public function setPaymentMethod($method)
    {
        $result = [];
        try {
            foreach ($this->getSubQuotes() as $subQuote) {
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
        $quotes = $this->getSubQuotes();
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
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws LocalizedException
     */
    public function createSubscriptions()
    {
        $profiles = [];
        $customer = $this->customerCreator->prepareCustomer();

        /** @var ModelQuote $subQuote */
        foreach ($this->getSubQuotes() as $subQuote) {
            $this->quoteCreator->fillCustomerData($customer, $subQuote);
            $this->quoteCreator->validate($subQuote);

            // Reset profile
            $this->profileManager->reset();

            // Fill profile
            $this->profileManager->populateProfileData($subQuote);

            // Fill profile payment
            $this->profileManager->populatePaymentData($subQuote->getPayment());

            try {
                // Process profile
                $order = $this->profileManager->processProfile($subQuote);
            } catch (\Exception $e) {
                $success = array_map(function (SubscriptionProfileInterface $profile) {
                    return $profile->getLabel();
                }, $profiles);

                $successMessage = !empty($success)
                    ? __('%1 profiles were paid successfully.', implode(', ', $success))
                    : '';

                throw new LocalizedException(__(
                    'Payment transaction error: %1. %2 Not paid subscription plans still in your cart.',
                    $e->getMessage(),
                    $successMessage
                ));
            }

            // Remove quote
            $this->getSession()->removeSubQuote($subQuote);

            // Save profile
            $profile = $this->profileManager->saveProfile();

            // Add comment about profile creation.
            $this->messageHistoryLogger->message(
                MessageHistoryLogger::MESSAGE_SUBSCRIPTION_CREATED,
                [
                    $profile->getLabel()
                ],
                $profile->getId()
            );

            // Add comment profile place.
            $this->messageHistoryLogger->message(
                MessageHistoryLogger::MESSAGE_ORDER_CREATED_FROM_QUOTE,
                [
                    $order->getIncrementId(),
                    $this->messageHistoryLogger->getConvertedQuoteId($subQuote->getId())
                ],
                $profile->getId()
            );

            $startDate = $profile->getTrialStartDate() ?: $profile->getStartDate();
            //Assign quote to new profile
            $relation = $this->profileManager->assignQuoteToProfile($subQuote, $profile, $startDate);
            //Add new relation to profile processing queue in "pending" state.
            $queueItemIds = $this->queueManager->insertItems([$relation->getId()]);
            $this->queueManager->makeRunning($queueItemIds);

            $this->profileManager->assignOrderToProfile($relation, $order);
            $this->queueManager->makeCompleted($queueItemIds);
            $this->eventManager->dispatch(
                'checkout_submit_all_after',
                ['order' => $order, 'quote' => $subQuote]
            );

            //Generate quote for next payment.
            $this->quoteGenerator->generateProfileQuotes($profile, 1);

            $profiles[] = $profile;
            //TODO add here email sending
        }

        return $profiles;
    }

    /**
     * Set selected currency to all subscription quotes and start recollect quotes.
     *
     * @param string $currencyCode
     * @return void
     */
    public function setCurrency($currencyCode)
    {
        $subQuotes = $this->getSubQuotes();
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
     * Reassign quotes by customer id.
     *
     * @param int $customerId
     * @return void
     */
    public function reassignQuote($customerId)
    {
        $customer = $this->customerCreator->getCustomer($customerId);
        foreach ($this->getSubQuotes() as $quote) {
            $quote->assignCustomer($customer);
            $this->quoteCreator->getCartRepository()->save($quote);
        }
    }

    /**
     * If customer on the first step is changed we need to change customer in all quotes.
     *
     * @return void
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
        $subQuotes = $this->getSubQuotes();
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
                //Otherwise we assign empty customer and empty addresses.
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
     * Clears extra data on payment and billing step
     * (ex. customer_address_id, shipping method).
     *
     * @return void
     */
    public function clearBillingStepData()
    {
        /** @var ModelQuote $subQuote */
        foreach ($this->getSubQuotes() as $subQuote) {
            $subQuote->getShippingAddress()->setShippingMethod('')->setShippingDescription('');
            $subQuote->getShippingAddress()->setCollectShippingRates(true);
            $this->setNeedCollect(true);
        }
    }

    /**
     * Returns product modifier.
     *
     * @return Product
     */
    public function getProductModifier()
    {
        return $this->productModifier;
    }

    /**
     * Returns quote address creator.
     *
     * @return Address
     */
    public function getAddressCreator()
    {
        return $this->addressCreator;
    }

    /**
     * Returns quote creator.
     *
     * @return QuoteCreateInterface
     */
    public function getQuoteCreator()
    {
        return $this->quoteCreator;
    }

    /**
     * Returns customer creator.
     *
     * @return Customer
     */
    public function getCustomerCreator()
    {
        return $this->customerCreator;
    }
}
