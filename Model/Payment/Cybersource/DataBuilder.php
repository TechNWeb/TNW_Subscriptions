<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Cybersource;

use \TNW\Subscriptions\Model\Config as SubscriptionConfig;
use \TNW\Subscriptions\Model\SubscriptionProfile\Manager;

class DataBuilder extends \TNW\Subscriptions\Model\Payment\DataBuilder
{
    use \Magento\Payment\Helper\Formatter;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var \Magento\GiftMessage\Helper\Message
     */
    protected $giftMessageHelper;

    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $auth;

    private $gatewayConfig;

    public function __construct(
        SubscriptionConfig $subscriptionConfig,
        Manager $manager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\GiftMessage\Helper\Message $giftMessageHelper,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->giftMessageHelper = $giftMessageHelper;
        $this->auth = $auth;
        if ($moduleManager->isEnabled("CyberSource_SecureAcceptance")) {
            $this->gatewayConfig = $objectManager->get("CyberSource\SecureAcceptance\Gateway\Config\Config");
        }
        parent::__construct($subscriptionConfig, $manager);
    }

    public function build($order, $paymentData)
    {
        $request = [];

        $request['partnerSolutionID'] = \CyberSource\Core\Helper\AbstractDataBuilder::PARTNER_SOLUTION_ID;
        $request['storeId'] = $order->getStoreId();
        $request['merchantID'] = $this->gatewayConfig->getValue(
            \CyberSource\SecureAcceptance\Gateway\Config\Config::KEY_MERCHANT_ID,
            $order->getStoreId()
        );
        $developerId = $this->gatewayConfig->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request['developerId'] = $developerId;
        }
        $order->reserveOrderId();
        $request['merchantReferenceCode'] = $order->getReservedOrderId();
        $amount = $this->getAmount($order);

        $request['purchaseTotals'] = [
            'currency' => $order->getCurrencyCode(),
            'grandTotalAmount' => $this->formatPrice($amount),
        ];
        $request['billTo'] = $this->buildAddress($order->getBillingAddress());

        if ($order->getShippingAddress()) {
            $request['shipTo'] = $this->buildAddress($order->getShippingAddress());
        }

        $quote = $this->getQuote();
        $result = [];
        $result['field1'] = (int)$this->customerSession->isLoggedIn();// Registered or Guest Account

        $orders = $this->getOrders();

        if ($this->customerSession->isLoggedIn()) {
            $result['field2'] = $this->getAccountCreationDate(); // Account Creation Date

            $result['field3'] = $orders->getSize(); // Purchase History Count

            if ($orders->getSize() > 0) {
                $result['field4'] = $orders->getFirstItem()->getCreatedAt(); // Last Order Date
            }

            $result['field5'] = $this->getAccountAge();// Member Account Age (Days)
        }

        $result['field6'] = (int)($orders->getSize() > 0); // Repeat Customer
        $result['field20'] = $quote->getCouponCode(); //Coupon Code

        $result['field21'] = $quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount(); // Discount

        $result['field22'] = $this->getGiftMessage(); // Gift Message

        $result['field23'] = ($this->auth->isLoggedIn()) ? 'call center' : 'web'; //order source

        if (!$quote->getIsVirtual()) {
            if ($shippingAddress = $quote->getShippingAddress()) {
                $result['field31'] = $quote->getShippingAddress()->getShippingMethod();
                $result['field32'] = $quote->getShippingAddress()->getShippingDescription();
            }
        }

        /**
         *  Remove invalid values before send it to cybersource, otherwise it will trigger a 403 without any error
         */
        foreach ($result as $key => $value) {
            if ($value !== null && !empty($value) && $value !== "" && $value !== null) {
                $request['merchantDefinedData'][$key] = $value;
            }
        }

        if ($fingerPrintId = $this->checkoutSession->getFingerprintId()) {
            $request['deviceFingerprintID'] = $fingerPrintId;

        }

        $request['billTo']['customerID'] = $order->getCustomerId();
        $request['billTo']['ipAddress'] = $order->getRemoteIp();
        $request['recurringSubscriptionInfo']['subscriptionID'] = $order->getPayment()->getAdditionalInformation(\CyberSource\SecureAcceptance\Model\PaymentTokenManagement::KEY_CYBERSOURCE_PAYMENT_TOKEN);
        $request['item'] =  $this->getItems($order->getAllItems());
        $request['ccAuthService']['run'] = 'true';
        return $request;
    }

    private function getItems($items)
    {
        $result = [];
        $i = 0;
        foreach ($items as $key => $item) {
            //getProductType used for order items, getOrderItem for invoice, creditmemo
            $type = $item->getProductType() ?: $item->getOrderItem()->getProductType();

            if ($item->getBasePrice() == 0) {
                continue;
            }

            if (in_array($type, [
                \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE
            ])) {
                continue;
            }

            //getQtyOrdered used for order items, getQty for invoice, creditmemo
            $qty = $item->getQty() ?: $item->getQtyOrdered();

            $price = $item->getBasePrice() - $item->getBaseDiscountAmount() / $qty;

            $result[$i] = [
                'id' => $i,
                'productName' => preg_replace("/[^a-zA-Z0-9\s]/", "", $item->getName()),
                'productSKU' => $item->getSku(),
                'productCode' => $type,
                'quantity' => (int)$qty,
                'unitPrice' => $this->formatPrice($price),
                'taxAmount' => $this->formatPrice($item->getBaseTaxAmount())
            ];
            $i++;
        }
        return $result;
    }

    private function buildAddress($address)
    {
        return [
            'firstName' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'company' => $address->getCompany(),
            'email' => $address->getEmail(),
            'street1' => $address->getStreetLine1(),
            'street2' => $address->getStreetLine2(),
            'city' => $address->getCity(),
            'state' => $address->getRegionCode(),
            'country' => $address->getCountryId(),
            'phone' => $address->getTelephone(),
            'postalCode' => $address->getPostcode(),
        ];
    }

    private function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    private function getOrders()
    {
        $field = 'customer_email';
        $value = $this->getQuote()->getCustomerEmail();
        if ($this->customerSession->isLoggedIn()) {
            $field = 'customer_id';
            $value = $this->customerSession->getCustomerId();
        }
        return $this->orderCollectionFactory->create()
            ->addFieldToFilter($field, $value)
            ->setOrder('created_at', 'desc');
    }

    private function getAccountCreationDate()
    {
        return $this->customerSession->getCustomerData()->getCreatedAt();
    }

    private function getAccountAge()
    {
        return round((time() - strtotime($this->customerSession->getCustomerData()->getCreatedAt())) / (3600 * 24));
    }

    private function getGiftMessage()
    {
        $message = $this->giftMessageHelper->getGiftMessage($this->getQuote()->getGiftMessageId());
        return $message->getMessage() ? $message->getMessage() : '';
    }


}
