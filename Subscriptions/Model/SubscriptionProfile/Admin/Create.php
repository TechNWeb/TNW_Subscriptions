<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin;

use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Model\Backend\Session\Quote as Session;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Address;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Quote;

/**
 * Class for creating subscriptions in admin.
 */
class Create extends AbstractCreate
{
    /**
     * Last part of path to unique subscription fields in product buy request.
     *
     * Using for checking the ability to add product to subscription quote.
     */
    const UNIQUE = '/unique';

    /**
     * Last part of path to non_unique fields in product buy request.
     */
    const NON_UNIQUE = '/non_unique';

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
     * Create constructor.
     * @param Context $context
     * @param Session $session
     * @param Address $addressCreator
     * @param Quote $quoteCreator
     * @param Product $productModifier
     */
    public function __construct(
        Context $context,
        Session $session,
        Address $addressCreator,
        Quote $quoteCreator,
        Product $productModifier
    ) {
        $this->addressCreator = $addressCreator;
        $this->quoteCreator = $quoteCreator;
        $this->productModifier = $productModifier;
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
            foreach ($this->getSession()->getSubQuotes() as $subQuote) {
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

        $subQuotes = $this->getSession()->getSubQuotes();

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

        $this->getSession()->addSubQuote($result);

        return $result;
    }

    /**
     * Checks whether it is possible to add a product in to quote.
     *
     * @param ModelQuote $subQuote
     * @return bool
     */
    private function canAddProduct($subQuote)
    {
        $result = false;

        $quoteItems = $subQuote->getAllItems();
        /** @var Item $item */
        $item = $quoteItems ? reset($quoteItems) : null;

        if ($item) {
            $request = $item->getBuyRequest()
                ->getDataByPath(static::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME . self::UNIQUE);

            $newRequest = $this->productModifier->getPreparedBuyRequest()
                ->getDataByPath(static::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME . self::UNIQUE);

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
     * @param $address
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
     * @param $address
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

        if (empty($result)) {
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

        $subQuotes = $this->getSession()->getSubQuotes();

        foreach ($subQuotes as $subQuote) {

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
            $subQuotes = $this->getSession()->getSubQuotes();

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
        $quotes = $this->getSession()->getSubQuotes();

        if (!empty($quotes)) {
            $quote = reset($quotes);

            $result = $quote->getPayment();
        } else {
            $result = false;
        }

        return $result;
    }
}
