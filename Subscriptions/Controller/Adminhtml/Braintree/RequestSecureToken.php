<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\Braintree;

use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Controller to get a secure token from PayPal.
 */
class RequestSecureToken extends \Magento\Framework\App\Action\Action
{
    const STATE_EDIT = 'edit';
    const STATE_NAME = 'state';

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
     * RequestSecureToken constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $session
     * @param \Magento\Framework\Session\Generic $sessionTransparent
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \TNW\Subscriptions\Model\QuoteSessionInterface $session,
        \Magento\Framework\Session\Generic $sessionTransparent,
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->session = $session;
        $this->sessionTransparent = $sessionTransparent;
        $this->profileManager = $profileManager;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
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
