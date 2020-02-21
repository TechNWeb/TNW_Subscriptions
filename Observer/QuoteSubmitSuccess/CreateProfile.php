<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer\QuoteSubmitSuccess;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class CreateProfile implements ObserverInterface
{
    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Manager
     */
    private $profileManager;

    /**
     * @var \TNW\Subscriptions\Model\Quote\ItemGroup
     */
    private $quoteItemGroup;

    /**
     * @var \Magento\Sales\Api\OrderCustomerManagementInterface
     */
    private $orderCustomerService;

    /**
     * @var \TNW\Subscriptions\Cron\Quote\Creator
     */
    private $quoteGenerator;

    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation
     */
    private $relationResource;

    private $customerFactory;

    /**
     * @var array
     */
    private $trialPaymentData = [];

    private $paymentTokenManagement;

    private $encryptor;

    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager,
        \TNW\Subscriptions\Model\Quote\ItemGroup $quoteItemGroup,
        \Magento\Sales\Api\OrderCustomerManagementInterface $orderCustomerService,
        \TNW\Subscriptions\Cron\Quote\Creator $quoteGenerator,
        \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation $relationResource,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->encryptor = $encryptor;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->profileManager = $profileManager;
        $this->quoteItemGroup = $quoteItemGroup;
        $this->orderCustomerService = $orderCustomerService;
        $this->quoteGenerator = $quoteGenerator;
        $this->relationResource = $relationResource;
        $this->customerFactory = $customerFactory;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getData('quote');
        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            return;
        }

        $order = $observer->getData('order');
        if (!$order instanceof \Magento\Sales\Model\Order || !$order->getEntityId()) {
            return;
        }

        $groups = array_filter($this->quoteItemGroup->groups($quote->getAllVisibleItems()), function($key) {
            return strcasecmp($key, 'no_option') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        if (empty($groups)) {
            return;
        }

        // Create customer
        if ($order->getCustomerIsGuest()) {
            $customer = $this->orderCustomerService->create($order->getEntityId());
            //ISSUE: https://github.com/magento/magento2/issues/7597
            $this->customerFactory->create()->setId($customer->getId())->reindex();
            $quote->setCustomer($customer);
        }

        if ($this->trialPaymentData) {
            $customer = $quote->getCustomer();
            $paymentToken = isset($this->trialPaymentData['payment_token'])
                ? $this->trialPaymentData['payment_token']
                : null;
            if ($paymentToken && !empty($paymentToken->getGatewayToken())) {
                $paymentData = $this->trialPaymentData['payment_data'];
                $paymentToken->setCustomerId($customer->getId());
                $paymentToken->setIsActive(true);
                $paymentToken->setPaymentMethodCode($paymentData['method']);
                $paymentToken->setIsVisible(true);
                $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
                $this->paymentTokenManagement->saveTokenWithPaymentLink($paymentToken, $order->getPayment());
                $this->trialPaymentData['vault_payment_token'] = $paymentToken;
            }
        }

        // Create profile
        $insertData = [];
        foreach ($groups as $groupKey => $quoteItems) {
            $profile = $this->profileManager->createByOrder($order, $quote, $quoteItems, $this->trialPaymentData);

            /** @var \TNW\Subscriptions\Model\ProductSubscriptionProfile $product */
            foreach ($profile->getProducts() as $product) {
                $quoteItemId = $product->getData('quote_item_id');
                if (empty($quoteItemId)) {
                    continue;
                }

                $orderItem = $order->getItemByQuoteItemId($quoteItemId);
                if (!$orderItem instanceof \Magento\Sales\Model\Order\Item) {
                    continue;
                }

                $insertData[] = [
                    'profile_item_id' => $product->getId(),
                    'quote_item_id' => $quoteItemId,
                    'order_item_id' => $orderItem->getId()
                ];
            }

            //Generate quote for next payment.
            $this->quoteGenerator->generateProfileQuotes($profile, 1);
        }

        // Save Items Relation
        $this->relationResource->insertSales($insertData);
    }

    /**
     * @param $data
     */
    public function setTrialPaymentData($data)
    {
        $this->trialPaymentData = $data;
    }

    /**
     * Generate vault payment public hash
     *
     * @param PaymentTokenInterface $paymentToken
     * @return string
     */
    protected function generatePublicHash(PaymentTokenInterface $paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }
}
