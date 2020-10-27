<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer\QuoteSubmitSuccess;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Class CreateProfile
 * @package TNW\Subscriptions\Observer\QuoteSubmitSuccess
 */
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

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;

    /**
     * @var array
     */
    private $trialPaymentData = [];

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var \TNW\Subscriptions\Plugin\Quote\Model\ChangeQuoteControl
     */
    private $changeQuoteControl;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var array
     */
    private $vaultTrialPaymentMap = [
        'tnw_authorize_cim_vault' => 'tnw_authorize_cim',
        'payflowpro_cc_vault' => 'payflowpro',
        'chcybersource_cc_vault' => 'chcybersource',
        'tnw_stripe_vault' => 'tnw_stripe'
    ];

    /**
     * CreateProfile constructor.
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager
     * @param \TNW\Subscriptions\Model\Quote\ItemGroup $quoteItemGroup
     * @param \Magento\Sales\Api\OrderCustomerManagementInterface $orderCustomerService
     * @param \TNW\Subscriptions\Cron\Quote\Creator $quoteGenerator
     * @param \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation $relationResource
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \TNW\Subscriptions\Plugin\Quote\Model\ChangeQuoteControl $changeQuoteControl
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager,
        \TNW\Subscriptions\Model\Quote\ItemGroup $quoteItemGroup,
        \Magento\Sales\Api\OrderCustomerManagementInterface $orderCustomerService,
        \TNW\Subscriptions\Cron\Quote\Creator $quoteGenerator,
        \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation $relationResource,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \TNW\Subscriptions\Plugin\Quote\Model\ChangeQuoteControl $changeQuoteControl,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->changeQuoteControl = $changeQuoteControl;
        $this->encryptor = $encryptor;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->profileManager = $profileManager;
        $this->quoteItemGroup = $quoteItemGroup;
        $this->orderCustomerService = $orderCustomerService;
        $this->quoteGenerator = $quoteGenerator;
        $this->relationResource = $relationResource;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
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

        $indexedGroups = array_filter($this->quoteItemGroup->groups($quote->getAllVisibleItems()), function($key) {
            return strcasecmp($key, 'no_option') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        if (empty($indexedGroups)) {
            return;
        }
        $groups = [];
        foreach ($indexedGroups as $indexedGroup) {
            foreach ($indexedGroup as $quoteItem) {
                $groups[][] = $quoteItem;
            }
        }

        // Create customer
        if ($order->getCustomerIsGuest()) {
            $customer = $this->orderCustomerService->create($order->getEntityId());
            //ISSUE: https://github.com/magento/magento2/issues/7597
            $this->customerFactory->create()->setId($customer->getId())->reindex();
            $quote->setCustomer($customer);
            $this->changeQuoteControl->setNewCustomer($customer);
        }

        // Compatibility with third-party modules which convert guest to customer
        if (!$order->getCustomerIsGuest() && $order->getCustomerId() && $quote->getCustomerIsGuest()) {
            $customer = $this->customerRepository->getById($order->getCustomerId());
            $quote->setCustomer($customer);
            $this->changeQuoteControl->setNewCustomer($customer);
        }

        if ($this->trialPaymentData) {
            $customer = $quote->getCustomer();
            $paymentToken = isset($this->trialPaymentData['payment_token'])
                ? $this->trialPaymentData['payment_token']
                : null;
            if ($paymentToken && !empty($paymentToken->getGatewayToken())) {
                $paymentData = $this->trialPaymentData['payment_data'];
                $paymentMethodCode = $paymentData['method'];

                if (isset($this->vaultTrialPaymentMap[$paymentMethodCode])) {
                    $paymentMethodCode = $this->vaultTrialPaymentMap[$paymentMethodCode];
                }
                $paymentToken->setCustomerId($customer->getId());
                $paymentToken->setIsActive(true);
                $paymentToken->setPaymentMethodCode($paymentMethodCode);
                $paymentToken->setIsVisible(true);
                $paymentToken->setType('card');
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
