<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use Magento\Payment\Model\Checks\ZeroTotal;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use Magento\Payment\Model\Method\Free;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Class Base
 */
class Base implements EngineInterface
{
    /**
     * Config model.
     *
     * @var Config
     */
    private $config;

    /**
     * @var Context
     */
    private $context;

    /**
     * Subscription profile.
     *
     * @var SubscriptionProfileInterface
     */
    private $profile;

    /**
     * Cart management.
     *
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * Profile comments logger.
     *
     * @var MessageHistoryLogger
     */
    private $historyLogger;

    /**
     * Registry.
     *
     * @var Registry
     */
    private $registry;

    /**
     * Data persistor
     *
     * @var DataPersistorInterface
     */
    private $persistor;

    /**
     * Zero total quote validator.
     *
     * @var ZeroTotal
     */
    private $zeroTotalValidator;

    /**
     * Base constructor.
     * @param Config $config
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param MessageHistoryLogger $historyLogger
     * @param Registry $registry
     * @param DataPersistorInterface $persistor
     */
    public function __construct(
        Config $config,
        Context $context,
        CartManagementInterface $cartManagement,
        MessageHistoryLogger $historyLogger,
        Registry $registry,
        DataPersistorInterface $persistor,
        ZeroTotal $zeroTotalValidator
    ) {
        $this->config = $config;
        $this->context = $context;
        $this->cartManagement = $cartManagement;
        $this->historyLogger = $historyLogger;
        $this->registry = $registry;
        $this->persistor = $persistor;
        $this->zeroTotalValidator = $zeroTotalValidator;
    }

    /**
     * Returns config object.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns context object.
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Returns cart management object.
     *
     * @return CartManagementInterface
     */
    public function getCartManagement()
    {
        return $this->cartManagement;
    }

    /**
     * Returns data persistor
     *
     * @return DataPersistorInterface
     */
    protected function getPersistor()
    {
        return $this->persistor;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * {@inheritdoc}
     */
    public function setProfile(SubscriptionProfileInterface $profile)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function processProfile(Quote $quote)
    {
        try {
            $this->validatePayment($quote);
            $order = $this->getCartManagement()->submit($quote);
            $this->logToMessageHistory($this->getProfile(), $quote, $order);
            $this->updateProfileStatus();

            return $order;
        } catch (\Exception $e) {
            $this->getProfile()->setStatus(ProfileStatus::STATUS_PAST_DUE);
            $quote->setReservedOrderId(null);
            $quote->save();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function processProfileByRequestData($requestData)
    {
        return $this;
    }

    /**
     * Log to comment profile comment history created order.
     *
     * @param SubscriptionProfileInterface $profile
     * @param Quote $quote
     * @param OrderInterface $order
     * @return void
     */
    private function logToMessageHistory(
        SubscriptionProfileInterface $profile,
        Quote $quote,
        OrderInterface $order
    ) {
        $message = sprintf(
            $this->historyLogger->getMessage(MessageHistoryLogger::MESSAGE_ORDER_CREATED_FROM_QUOTE),
            $order->getIncrementId(),
            $this->historyLogger->getConvertedQuoteId($quote->getId())
        );

        $this->historyLogger->log(
            $message,
            $profile->getId(),
            false,
            false,
            $this->registry->registry('profile_process_type')
        );
    }

    /**
     * Updates profile status after order processing
     */
    private function updateProfileStatus()
    {
        $status = ProfileStatus::STATUS_ACTIVE;
        $trialStartDate = $this->getProfile()->getTrialStartDate();
        $startDate = $this->getProfile()->getStartDate();
        if ($trialStartDate) {
            $date = (new \DateTime())->getTimestamp();
            $startDate = (new \DateTime($startDate))->getTimestamp();
            if ($date < $startDate) {
                $status = ProfileStatus::STATUS_TRIAL;
            }
        }
        $this->getProfile()->setStatus($status);
    }

    /**
     * Validates zero total and sets free payment method to quote if validation failed.
     *
     * @param Quote $quote
     */
    private function validatePayment(Quote $quote)
    {
        /** @var Payment $payment */
        $payment = $quote->getPayment();
        $payment->importData($this->getPaymentInfo($this->getProfile()));
        $payment->setAdditionalInformation($this->getPaymentAdditionalInfo($this->getProfile()));

        // check quote total
        if (!$this->zeroTotalValidator->isApplicable($payment->getMethodInstance(), $quote)) {
            $payment->importData(['method' => Free::PAYMENT_METHOD_FREE_CODE]);
            $payment->setAdditionalInformation([]);
        }
    }
}