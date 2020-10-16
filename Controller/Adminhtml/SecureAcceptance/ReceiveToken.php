<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SecureAcceptance;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Payment\Block\Transparent\Iframe;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryPaymentMethodForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Class ReceiveToken - controller used to get the token from cybersource
 */
class ReceiveToken extends Action
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
     * ReceiveToken constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param LayoutFactory $resultLayoutFactory
     * @param ProfileManager $profileManager
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        LayoutFactory $resultLayoutFactory,
        ProfileManager $profileManager
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->profileManager = $profileManager;
    }

    /**
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $parameters = [];
        $profile = null;
        if ($this->getRequest()->getParam(SummaryInsertForm::FORM_DATA_KEY, 0)) {
            /** @var SubscriptionProfileInterface $profile */
            $profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        }
        if ($this->getRequest()->getParam('isAjax', false)) {
            $parameters['payment_token'] = $this->_session->getData('chcybersource_payment_token');
            $this->_session->setData('chcybersource_payment_token', null);
        } else {
            $this->_session->setData(
                'chcybersource_payment_token',
                $this->getRequest()->getParam('payment_token')
            );
            $parameters['payment_token'] = $this->getRequest()->getParam('payment_token');
        }

        $this->coreRegistry->register(Iframe::REGISTRY_KEY, $parameters);

        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->addDefaultHandle();
        $resultLayout->getLayout()->getUpdate()->load(['tnw_cybersource_payment_response']);
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
        return $profile ? SummaryPaymentMethodForm::FORM_NAME : Payment::DATA_SCOPE_PAYMENT_FORM;
    }

    /**
     * @return bool
     */
    public function _processUrlKeys()
    {
        $_isValidFormKey = true;
        $_isValidSecretKey = true;
        $_keyErrorMsg = '';
        if ($this->_auth->isLoggedIn()) {
            if ($this->_session->getData('chcybersource_security_key')) {
                $_isValidSecretKey = $this->_session->getData('chcybersource_security_key')
                    == $this->getRequest()->getParam('req_transaction_uuid');
                $_keyErrorMsg = __('You entered an invalid Secret Key. Please refresh the page.');
                $this->_session->setData('chcybersource_security_key', null);
            } elseif ($this->getRequest()->isPost()) {
                $_isValidFormKey = $this->_formKeyValidator->validate($this->getRequest());
                $_keyErrorMsg = __('Invalid Form Key. Please refresh the page.');
            } elseif ($this->_backendUrl->useSecretKey()) {
                $_isValidSecretKey = $this->_validateSecretKey();
                $_keyErrorMsg = __('You entered an invalid Secret Key. Please refresh the page.');
            }
        }
        if (!$_isValidFormKey || !$_isValidSecretKey) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);
            if ($this->getRequest()->getQuery('isAjax', false) || $this->getRequest()->getQuery('ajax', false)) {
                $this->getResponse()->representJson(
                    $this->_objectManager->get(
                        \Magento\Framework\Json\Helper\Data::class
                    )->jsonEncode(
                        ['error' => true, 'message' => $_keyErrorMsg]
                    )
                );
            } else {
                $this->_redirect($this->_backendUrl->getStartupPageUrl());
            }
            return false;
        }
        return true;
    }
}
