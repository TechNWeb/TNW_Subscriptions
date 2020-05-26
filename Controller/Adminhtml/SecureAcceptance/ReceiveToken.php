<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SecureAcceptance;

/**
 * Class ReceiveToken
 * @package TNW\Subscriptions\Controller\Adminhtml\SecureAcceptance
 */
class ReceiveToken extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * ReceiveToken constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('isAjax', false)) {
            $result = $this->resultJsonFactory->create();
            $result->setData([
                'payment_token' =>  $this->_session->getData('chcybersource_payment_token')
            ]);
            $this->_session->setData('chcybersource_payment_token', null);
        } else {
            $result = $this->resultJsonFactory->create();
            $this->_session->setData(
                'chcybersource_payment_token',
                $this->getRequest()->getParam('payment_token')
            );
            $result->setData(['success' => true, 'payment_token' => $this->getRequest()->getParam('payment_token')]);
        }

        return $result;
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
