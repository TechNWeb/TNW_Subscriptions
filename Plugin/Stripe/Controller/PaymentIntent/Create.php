<?php
/**
 * Copyright © 2023 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Stripe\Controller\PaymentIntent;

use Magento\Checkout\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;
use TNW\Stripe\Controller\PaymentIntent\Create as CreateController;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Plugin class. Prevents Stripe payment intent creation for guest customers with already registered email.
 */
class Create
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var StripeAdapterFactory
     */
    private $stripeAdapterFactory;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Create constructor.
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param Json $jsonSerializer
     * @param StripeAdapterFactory $stripeAdapterFactory
     * @param Session $checkoutSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ResultFactory $resultFactory,
        RequestInterface $request,
        Json $jsonSerializer,
        StripeAdapterFactory $stripeAdapterFactory,
        Session $checkoutSession,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            $this->stripeAdapterFactory = $objectManager->get(StripeAdapterFactory::class);
        }
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->jsonSerializer = $jsonSerializer;
        $this->stripeAdapterFactory = $stripeAdapterFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * @param CreateController $subject
     * @param callable $proceed
     * @return ResultInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function aroundExecute(CreateController $subject, callable $proceed)
    {
        if ($this->checkoutSession->getQuote()->getCustomerIsGuest()
            && $this->isSubscriptionQuote($this->checkoutSession->getQuote())
        ) {
            try {
                $data = $this->jsonSerializer->unserialize($this->request->getParam('data'));
                $paymentMethodId = $data['paymentMethod']['id'];
                $stripeAdapter = $this->stripeAdapterFactory->create();
                $paymentMethod = $stripeAdapter->retrievePaymentMethod($paymentMethodId);
                $email = $paymentMethod->billing_details->email;
                if ($email && $this->isExistingCustomerEmail($email)) {
                    return $this->makeErrorResponse(
                        __('Already registered customer should be logged in to purchase subscription product.')
                    );
                }
            } catch (\Throwable $exception) {
                $this->logger->error("Couldn't check customer existence.", compact('exception'));
            }
        }

        return $proceed();
    }

    /**
     * @param CartInterface $quote
     * @return bool
     */
    private function isSubscriptionQuote(CartInterface $quote): bool
    {
        foreach ($quote->getItems() as $item) {
            $options = $item->getBuyRequest();
            if (!empty($options['subscribe_active'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $email
     * @return bool
     */
    private function isExistingCustomerEmail(string $email): bool
    {
        try {
            $this->customerRepository->get($email);
            return true;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @param string $message
     * @return ResultInterface
     */
    private function makeErrorResponse(string $message): ResultInterface
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData([
            'error' => [
                'message' => $message
            ]
        ]);
        return $response;
    }
}
