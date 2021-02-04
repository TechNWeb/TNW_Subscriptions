<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Vault\Model;

/**
 * Class PaymentTokenManagement - plugin to add additional logic to after token save
 */
class PaymentTokenManagement
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * PaymentTokenManagement constructor.
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $repository
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $repository,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->encryptor = $encryptor;
        $this->paymentTokenRepository = $repository;
    }

    /**
     * @param \Magento\Vault\Model\PaymentTokenManagement $subject
     * @param $proceed
     * @param $token
     * @param $payment
     * @return mixed
     */
    public function aroundSaveTokenWithPaymentLink($subject, $proceed, $token, $payment)
    {
        $order = $payment->getOrder();
        if ($order && $order->getCustomerIsGuest()) {
            $subscriptionCreation = false;
            foreach ($order->getItems() as $item) {
                $productOptions = $item->getProductOPtions();
                if (array_key_exists('info_buyRequest', $productOptions)
                && array_key_exists('subscribe_active', $productOptions['info_buyRequest'])
                    && $productOptions['info_buyRequest']['subscribe_active']
                ) {
                    $subscriptionCreation = true;
                    break;
                }
            }
            if ($order->getCustomerEmail() && !$token->getCustomerId() && $subscriptionCreation) {
                try {
                    $customer = $this->customerRepository->get(
                        $order->getCustomerEmail(),
                        $this->storeManager->getStore($order->getStoreId())->getWebsiteId()
                    );
                } catch (\Exception $e) {
                    $customer = null;
                }
                if ($customer && $customer->getId()) {
                    $token->setCustomerId($customer->getId());
                }
            }
        }

        $tokenDuplicate = $subject->getByGatewayToken(
            $token->getGatewayToken(),
            $token->getPaymentMethodCode(),
            $token->getCustomerId()
        );
        if ($token->getDetails() == "null" && $payment->getMethod() == "payflowpro") {
            $token->setDetails(json_encode([
                'cc_type' => $payment->getCcType(),
                'cc_exp_year' => $payment->getCcExpYear(),
                'cc_exp_month' => $payment->getCcExpMonth(),
                'cc_last_4' => $payment->getCcLast4()
            ]));
        }

        if (!empty($tokenDuplicate)) {
            if ($token->getIsVisible() || $tokenDuplicate->getIsVisible()) {
                $token->setEntityId($tokenDuplicate->getEntityId());
                $token->setPublicHash($tokenDuplicate->getPublicHash());
                $token->setIsVisible(true);
            } elseif ($token->getIsVisible() === $tokenDuplicate->getIsVisible()) {
                $token->setEntityId($tokenDuplicate->getEntityId());
            } else {
                $token->setPublicHash(
                    $this->encryptor->getHash(
                        $token->getPublicHash() . $token->getGatewayToken()
                    )
                );
            }
            $this->paymentTokenRepository->save($token);
            $result = $subject->addLinkToOrderPayment($token->getEntityId(), $payment->getEntityId());
        } else {
            $result = $proceed($token, $payment);
        }

        return $result;
    }
}
