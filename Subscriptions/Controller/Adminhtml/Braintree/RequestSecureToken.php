<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\Braintree;

use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\EngineInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Controller to get a secure token from PayPal.
 */
class RequestSecureToken extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    private $sessionTransparent;

    /**
     * Admin session.
     *
     * @var \TNW\Subscriptions\Model\QuoteSessionInterface
     */
    private $session;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Manager
     */
    private $profileManager;

    /**
     * @var \Magento\Braintree\Model\Adapter\BraintreeAdapter
     */
    private $braintreeAdapter;

    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * RequestSecureToken constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $session
     * @param \Magento\Framework\Session\Generic $sessionTransparent
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager
     * @param \Magento\Braintree\Model\Adapter\BraintreeAdapter $braintreeAdapter
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \TNW\Subscriptions\Model\QuoteSessionInterface $session,
        \Magento\Framework\Session\Generic $sessionTransparent,
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager,
        \Magento\Braintree\Model\Adapter\BraintreeAdapter $braintreeAdapter,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->session = $session;
        $this->sessionTransparent = $sessionTransparent;
        $this->profileManager = $profileManager;
        $this->braintreeAdapter = $braintreeAdapter;
        $this->dataPersistor = $dataPersistor;
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $nonce = $this->getRequest()->getParam('nonce');
        if (empty($nonce)) {
            return $this->getErrorResponse();
        }

        if ($this->getRequest()->getParam(SummaryInsertForm::FORM_DATA_KEY, 0)) {
            $profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
            $object = $profile;
        } else {
            /** @var \Magento\Framework\DataObject $object */
            $object = $this->session->getFirstQuote();
            if (!$object instanceof \Magento\Quote\Model\Quote) {
                return $this->getErrorResponse();
            }

            $this->sessionTransparent->setQuoteId($object->getId());
        }

        try {
            try {
                \Braintree\Customer::find("magento_{$object->getCustomerId()}");
                $result = \Braintree\Customer::update("magento_{$object->getCustomerId()}", [
                    'firstName' => $object->getCustomer()->getFirstname(),
                    'lastName' => $object->getCustomer()->getLastname(),
                    'email' => $object->getCustomer()->getEmail(),
                    'paymentMethodNonce' => $nonce
                ]);
            } catch (\Braintree\Exception\NotFound $e) {
                $result = \Braintree\Customer::create([
                    'id' => "magento_{$object->getCustomerId()}",
                    'firstName' => $object->getCustomer()->getFirstname(),
                    'lastName' => $object->getCustomer()->getLastname(),
                    'email' => $object->getCustomer()->getEmail(),
                    'paymentMethodNonce' => $nonce
                ]);
            }

            if (!$result->success) {
                $errors = [];
                foreach($result->errors->deepAll() AS $error) {
                    $errors[] = "{$error->code}: {$error->message}";
                }

                throw new \Exception(implode("\n ", $errors));
            }

            if (isset($profile)) {
                $this->dataPersistor->set(EngineInterface::PAYMENT_DATA_KEY, [
                    SubscriptionProfileInterface::ID => $profile->getId(),
                    SubscriptionProfileInterface::TOKEN_HASH => $this->encryptor->encrypt($result->customer->paymentMethods[0]->token),
                ]);
            } else {
                //$this->transaction->savePaymentInQuote($response);
            }

            return $this->resultJsonFactory->create()->setData(
                [
                    'success' => true,
                    'error' => false
                ]
            );
        } catch (\Exception $e) {
            return $this->getErrorResponse();
        }
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function getErrorResponse()
    {
        return $this->resultJsonFactory->create()->setData(
            [
                'success' => false,
                'error' => true,
                'error_messages' => [
                    __('Your payment has been declined. Please try again.')
                ]
            ]
        );
    }
}
