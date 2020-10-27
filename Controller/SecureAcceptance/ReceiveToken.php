<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\SecureAcceptance;

use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\App\Request\InvalidRequestException;

/**
 * Class ReceiveToken
 * @package TNW\Subscriptions\Controller\SecureAcceptance
 */
class ReceiveToken
    extends \Magento\Framework\App\Action\Action
    implements \Magento\Framework\App\CsrfAwareActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * ReceiveToken constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $result->setData(['success' => true, 'payment_token' => $this->getRequest()->getParam('payment_token')]);
        return $result;
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
        if (
            $request->getParam('req_transaction_uuid')
            == $this->customerSession->getData('chcybersource_security_key')
        ) {
            $this->customerSession->setData('chcybersource_security_key', null);
            $result = true;
        }
        return $result;
    }
}
