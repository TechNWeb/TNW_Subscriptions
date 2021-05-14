<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\Paypal;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Payment\Block\Transparent\Iframe;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryPaymentMethodForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\EngineInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use Magento\Framework\Session\Generic;

/**
 * Controller to processing response from PayPal gateway.
 */
class Response extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpPostActionInterface
{
    /**
     * Core registry
     *
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var mixed
     */
    private $transaction;

    /**
     * @var mixed
     */
    private $responseValidator;

    /**
     * @var LayoutFactory
     */
    private $resultLayoutFactory;

    /**
     * @var mixed
     */
    private $transparent;

    /**
     * Profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * Data persistor (session)
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * Encryptor
     *
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var QuoteSessionInterface
     */
    private $quoteSession;

    /**
     * @var Generic
     */
    private $sessionTransparent;

    /**
     * Response constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param LayoutFactory $resultLayoutFactory
     * @param ProfileManager $profileManager
     * @param DataPersistorInterface $dataPersistor
     * @param EncryptorInterface $encryptor
     * @param QuoteSessionInterface $quoteSession
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     * @param Generic $sessionTransparent
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        LayoutFactory $resultLayoutFactory,
        ProfileManager $profileManager,
        DataPersistorInterface $dataPersistor,
        EncryptorInterface $encryptor,
        QuoteSessionInterface $quoteSession,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        Generic $sessionTransparent
    ) {
        parent::__construct($context);
        $this->sessionTransparent = $sessionTransparent;
        $this->quoteSession = $quoteSession;
        $this->coreRegistry = $coreRegistry;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->profileManager = $profileManager;
        $this->dataPersistor = $dataPersistor;
        $this->encryptor = $encryptor;
        if ($moduleManager->isEnabled("Magento_Paypal")) {
            $this->transaction = $objectManager->get(\Magento\Paypal\Model\Payflow\Service\Response\Transaction::class);
            $this->responseValidator = $objectManager
                ->get(\Magento\Paypal\Model\Payflow\Service\Response\Validator\ResponseValidator::class);
            $this->transparent = $objectManager->get(\Magento\Paypal\Model\Payflow\Transparent::class);
            ;
        }
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout
     * @throws LocalizedException
     */
    public function execute()
    {
        $parameters = [];
        $profile = null;
        if ($this->getRequest()->getParam(SummaryInsertForm::FORM_DATA_KEY, 0)) {
            /** @var SubscriptionProfileInterface $profile */
            $profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        } elseif ($profileId = $this->sessionTransparent->getSubscriptionProfileId()) {
            $profile = $this->profileManager->loadProfile($profileId);
            $this->sessionTransparent->setSubscriptionProfileId(null);
        }
        try {
            /** @var DataObject $response */
            $response = $this->transaction->getResponseObject($this->getRequest()->getPostValue());
            $this->responseValidator->validate($response, $this->transparent);
            $pnref = $response->getPnref();
            if (isset($profile)) {
                $this->dataPersistor->set(
                    EngineInterface::PAYMENT_DATA_KEY,
                    [
                        SubscriptionProfileInterface::ID => $profile->getId(),
                        SubscriptionProfilePaymentInterface::TOKEN_HASH => $this->encryptor->encrypt($pnref),
                        'pnref' => $pnref
                    ]
                );
            } else {
                $this->quoteSession->setData('pnref', $pnref);
            }
        } catch (LocalizedException $exception) {
            $parameters['error'] = true;
            $parameters['error_messages'] = [$exception->getMessage()];
        }

        $this->coreRegistry->register(Iframe::REGISTRY_KEY, $parameters);

        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->addDefaultHandle();
        $resultLayout->getLayout()->getUpdate()->load(['paypal_payment_response']);
        /** @var AbstractBlock $iframeBlock */
        $iframeBlock = $resultLayout->getLayout()->getBlock('transparent_iframe');
        $index = $this->getFormIndex($profile);
        $iframeBlock->setData('index', $index);

        return $resultLayout;
    }

    /**
     * Returns response form index.
     *
     * @param null|SubscriptionProfileInterface $profile
     * @return string
     */
    protected function getFormIndex($profile = null)
    {
        $index = isset($profile)
            ? SummaryPaymentMethodForm::FORM_NAME
            : Payment::DATA_SCOPE_PAYMENT_FORM;

        return $index;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        if (!$request->getPostValue('SECURETOKEN')) {
            return false;
        }
        return $this->quoteSession->getData('secure_token', true) == $request->getPostValue('SECURETOKEN');
    }
}
