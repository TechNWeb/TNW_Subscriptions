<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Cybersource\Gateway\Request\Soap;

/**
 * Class DecisionManagerMddBuilder - modified data builder for re-bill processing
 */
class DecisionManagerMddBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
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
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $auth;

    /**
     * DecisionManagerMddBuilder constructor.
     *
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Session\SessionManagerInterface $checkoutSession
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\GiftMessage\Helper\Message $giftMessageHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $checkoutSession,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\GiftMessage\Helper\Message $giftMessageHelper,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        if ($moduleManager->isEnabled("CyberSource_SecureAcceptance")) {
            $this->subjectReader = $objectManager
                ->get(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        }
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->giftMessageHelper = $giftMessageHelper;
        $this->cartRepository = $cartRepository;
        $this->auth = $auth;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        $quote = $this->getQuote();

        $request = [];
        $result = [];

        $result['field1'] = (int) $this->customerSession->isLoggedIn();// Registered or Guest Account

        $orders = $this->getOrders();

        if ($this->customerSession->isLoggedIn()) {
            $result['field2'] = $this->getAccountCreationDate(); // Account Creation Date

            if ($orders) {
                $result['field3'] = $orders->getSize(); // Purchase History Count
            }

            if ($orders && $orders->getSize() > 0) {
                $result['field4'] = $orders->getFirstItem()->getCreatedAt(); // Last Order Date
            }

            $result['field5'] = $this->getAccountAge();// Member Account Age (Days)
        }

        $result['field6'] = $orders ? (int) ($orders->getSize() > 0) : false; // Repeat Customer
        if ($quote) {
            $result['field20'] = $quote->getCouponCode(); //Coupon Code

            $result['field21'] = $quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount(); // Discount
        }

        $result['field22'] = $this->getGiftMessage(); // Gift Message

        $result['field23'] = ($this->auth->isLoggedIn()) ? 'call center' : 'web'; //order source

        if ($quote && !$quote->getIsVirtual()) {
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

        return $request;
    }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface|null
     */
    private function getQuote()
    {
        try {
            return $this->cartRepository->get($this->checkoutSession->getQuoteId());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array|\Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private function getOrders()
    {
        $field = 'customer_email';
        if ($this->getQuote()) {
            $value = $this->getQuote()->getCustomerEmail();
            if ($this->customerSession->isLoggedIn()) {
                $field = 'customer_id';
                $value = $this->customerSession->getCustomerId();
            }
            return $this->orderCollectionFactory->create()
                ->addFieldToFilter($field, $value)
                ->setOrder('created_at', 'desc');
        } else {
            return [];
        }
    }

    /**
     * @return string|null
     */
    private function getAccountCreationDate()
    {
        try {
            return $this->customerSession->getCustomerData()->getCreatedAt();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return float
     */
    private function getAccountAge()
    {
        return round((time() - strtotime($this->getAccountCreationDate())) / (3600 * 24));
    }

    /**
     * @return mixed|string
     */
    private function getGiftMessage()
    {
        if ($this->getQuote()) {
            $message = $this->giftMessageHelper->getGiftMessage($this->getQuote()->getGiftMessageId());
            return $message->getMessage() ? $message->getMessage() : '';
        } else {
            return '';
        }
    }
}
