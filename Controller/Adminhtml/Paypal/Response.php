<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\Paypal;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Registry;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Block\Transparent\Iframe;
use Magento\Paypal\Model\Payflow\Service\Response\Transaction;
use Magento\Paypal\Model\Payflow\Service\Response\Validator\ResponseValidator;
use Magento\Paypal\Model\Payflow\Transparent;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryPaymentMethodForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\EngineInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use TNW\Subscriptions\Model\QuoteSessionInterface;

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
     * @var Transaction
     */
    private $transaction;

    /**
     * @var ResponseValidator
     */
    private $responseValidator;

    /**
     * @var LayoutFactory
     */
    private $resultLayoutFactory;

    /**
     * @var Transparent
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

    private $quoteSession;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Transaction $transaction
     * @param ResponseValidator $responseValidator
     * @param LayoutFactory $resultLayoutFactory
     * @param Transparent $transparent
     * @param ProfileManager $profileManager
     * @param DataPersistorInterface $dataPersistor
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Transaction $transaction,
        ResponseValidator $responseValidator,
        LayoutFactory $resultLayoutFactory,
        Transparent $transparent,
        ProfileManager $profileManager,
        DataPersistorInterface $dataPersistor,
        EncryptorInterface $encryptor,
        QuoteSessionInterface $quoteSession
    ) {
        parent::__construct($context);
        $this->quoteSession = $quoteSession;
        $this->coreRegistry = $coreRegistry;
        $this->transaction = $transaction;
        $this->responseValidator = $responseValidator;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->transparent = $transparent;
        $this->profileManager = $profileManager;
        $this->dataPersistor = $dataPersistor;
        $this->encryptor = $encryptor;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $parameters = [];
        $profile = null;
        if ($this->getRequest()->getParam(SummaryInsertForm::FORM_DATA_KEY, 0)) {
            /** @var SubscriptionProfileInterface $profile */
            $profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        }
        try {
            /** @var DataObject $response */
            $response = $this->transaction->getResponseObject($this->getRequest()->getPostValue());
            $this->responseValidator->validate($response, $this->transparent);
            $pnref = $response->getPnref();
            if (isset($profile)) {
                $this->dataPersistor->set(EngineInterface::PAYMENT_DATA_KEY,
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
        //TODO: implement csrf validation via secure token
        return true;
    }
}
