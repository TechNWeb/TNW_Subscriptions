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
use Magento\Backend\Model\Session\Quote as SessionQuote;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use TNW\Subscriptions\Model\Quote\ItemGroup;
use Magento\Sales\Api\OrderCustomerManagementInterface;
use TNW\Subscriptions\Cron\Quote\Creator;
use TNW\Subscriptions\Model\ResourceModel\SalesItemRelation;
use Magento\Customer\Model\CustomerFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use TNW\Subscriptions\Plugin\Quote\Model\ChangeQuoteControl;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Item;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

/**
 * Class CreateProfile - observer
 */
class CreateProfile implements ObserverInterface
{
    /**
     * @var Manager
     */
    private $profileManager;

    /**
     * @var ItemGroup
     */
    private $quoteItemGroup;

    /**
     * @var OrderCustomerManagementInterface
     */
    private $orderCustomerService;

    /**
     * @var Creator
     */
    private $quoteGenerator;

    /**
     * @var SalesItemRelation
     */
    private $relationResource;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var array
     */
    private $trialPaymentData = [];

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ChangeQuoteControl
     */
    private $changeQuoteControl;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SessionQuote
     */
    private $sessionQuote;

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
     * @param Manager $profileManager
     * @param ItemGroup $quoteItemGroup
     * @param OrderCustomerManagementInterface $orderCustomerService
     * @param Creator $quoteGenerator
     * @param SalesItemRelation $relationResource
     * @param CustomerFactory $customerFactory
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param EncryptorInterface $encryptor
     * @param ChangeQuoteControl $changeQuoteControl
     * @param CustomerRepositoryInterface $customerRepository
     * @param SessionQuote $sessionQuote
     */
    public function __construct(
        Manager $profileManager,
        ItemGroup $quoteItemGroup,
        OrderCustomerManagementInterface $orderCustomerService,
        Creator $quoteGenerator,
        SalesItemRelation $relationResource,
        CustomerFactory $customerFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        EncryptorInterface $encryptor,
        ChangeQuoteControl $changeQuoteControl,
        CustomerRepositoryInterface $customerRepository,
        SessionQuote $sessionQuote
    ) {
        $this->sessionQuote = $sessionQuote;
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
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getData('quote');
        if (!$quote instanceof Quote) {
            return;
        }

        $order = $observer->getData('order');
        if (!$order instanceof Order || !$order->getEntityId()) {
            return;
        }
        $paymentAdditionalInfo = $order->getPayment()->getAdditionalInformation();
        if (is_array($paymentAdditionalInfo)
            && isset($paymentAdditionalInfo['is_rebill'])
            && $paymentAdditionalInfo['is_rebill']
        ) {
            return;
        }
        $indexedGroups = array_filter($this->quoteItemGroup->groups($quote->getAllVisibleItems()), function ($key) {
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
            try {
                $customer = $this->orderCustomerService->create($order->getEntityId());
                //ISSUE: https://github.com/magento/magento2/issues/7597
                $this->customerFactory->create()->setId($customer->getId())->reindex();
                if (!$this->trialPaymentData) {
                    $payment = $order->getPayment();
                    if ($payment->getExtensionAttributes()
                        && $payment->getExtensionAttributes()->getVaultPaymentToken()
                        && $payment->getExtensionAttributes()->getVaultPaymentToken()->getEntityId()
                        && !$payment->getExtensionAttributes()->getVaultPaymentToken()->getCustomerId()
                    ) {
                        $vaultPaymentToken = $payment->getExtensionAttributes()->getVaultPaymentToken();
                        $vaultPaymentToken->setCustomerId($customer->getId());
                        $this->populatePaymentTokenWithLiabilityInfo($payment, $vaultPaymentToken);
                        $this->paymentTokenManagement->saveTokenWithPaymentLink($vaultPaymentToken, $payment);
                    }
                }
            } catch (\Exception $e) {
                $customer = $this->customerRepository->get($order->getCustomerEmail());
            }
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
                $this->populatePaymentTokenWithLiabilityInfo($order->getPayment(), $paymentToken);
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
                if (!$orderItem instanceof Item) {
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
        $this->profileManager->setProfilesToCalculateProfit($order);
        $this->sessionQuote->setStoreId(0);
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

    /**
     * @param $payment
     * @param $vaultPaymentToken
     */
    private function populatePaymentTokenWithLiabilityInfo($payment, $vaultPaymentToken)
    {
        if ($payment->getAdditionalInformation('liabilityShifted')
            && $payment->getAdditionalInformation('eciFlag') == 'Success'
        ) {
            $vaultPaymentToken->setData(
                'liability_shift_possible',
                $payment->getAdditionalInformation('liabilityShiftPossible') == 'Yes' ? 1 : 0
            );
            $vaultPaymentToken->setData(
                'liability_shifted',
                $payment->getAdditionalInformation('liabilityShifted') == 'Yes' ? 1 : 0
            );
        }
    }
}
