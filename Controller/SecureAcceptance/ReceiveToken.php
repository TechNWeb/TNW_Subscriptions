<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\SecureAcceptance;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Payment\Block\Transparent\Iframe;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout\Payment;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Customer\Account\PaymentMethodForm;

/**
 * Class ReceiveToken - controller
 */
class ReceiveToken extends Action implements CsrfAwareActionInterface
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var LayoutFactory
     */
    private $resultLayoutFactory;

    /**
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * ReceiveToken constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Session $customerSession
     * @param Registry $coreRegistry
     * @param LayoutFactory $resultLayoutFactory
     * @param ProfileManager $profileManager
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $customerSession,
        Registry $coreRegistry,
        LayoutFactory $resultLayoutFactory,
        ProfileManager $profileManager,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->coreRegistry = $coreRegistry;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->profileManager = $profileManager;
        $this->messageManager = $messageManager;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $profile = null;
        if ($this->getRequest()->getParam(SummaryInsertForm::FORM_DATA_KEY, 0)) {
            /** @var SubscriptionProfileInterface $profile */
            $profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        } elseif ($profileId = $this->customerSession->getData('subscription_profile_id')) {
            $profile = $this->profileManager->loadProfile($profileId);
            $this->customerSession->setData('subscription_profile_id', null);
        }
        if ($this->getRequest()->getParam('isAjax', false)) {
            $paymentToken = $this->customerSession->getData('chcybersource_payment_token');
            $this->customerSession->setData('chcybersource_payment_token', null);
        } else {
            $this->customerSession->setData(
                'chcybersource_payment_token',
                $this->getRequest()->getParam('payment_token')
            );
            $paymentToken = $this->getRequest()->getParam('payment_token');
        }

        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->addDefaultHandle();
        try {
            $resultLayout->getLayout()->getUpdate()->load(['tnw_cybersource_payment_response']);
        } catch (LocalizedException $localizedException) {
            $this->messageManager->addErrorMessage($localizedException->getMessage());
        }
        /** @var AbstractBlock $iframeBlock */
        $iframeBlock = $resultLayout->getLayout()->getBlock('transparent_iframe');
        $index = $this->getFormIndex($profile);
        $iframeBlock->setData('index', $index);
        $iframeBlock->setData('payment_token', $paymentToken);
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
        return $profile ? PaymentMethodForm::FORM_NAME : Payment::DATA_SCOPE_PAYMENT_FORM;
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        $result = false;
        if ($request->getParam('req_transaction_uuid')
            == $this->customerSession->getData('chcybersource_security_key')
        ) {
            $this->customerSession->setData('chcybersource_security_key', null);
            $result = true;
        }
        return $result;
    }
}
