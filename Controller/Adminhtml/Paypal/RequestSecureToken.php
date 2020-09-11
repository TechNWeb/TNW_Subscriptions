<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\Paypal;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Session\Generic;
use TNW\Subscriptions\Model\Payment\Paypal\SecureToken;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Controller to get a secure token from PayPal.
 */
class RequestSecureToken extends \Magento\Framework\App\Action\Action
{
    /**
     *
     */
    const STATE_EDIT = 'edit';
    /**
     *
     */
    const STATE_NAME = 'state';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Generic
     */
    private $sessionTransparent;

    /**
     * @var SecureToken
     */
    private $secureTokenService;

    /**
     * Admin session.
     *
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @var mixed
     */
    private $transparent;

    /**
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * RequestSecureToken constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param QuoteSessionInterface $session
     * @param Generic $sessionTransparent
     * @param SecureToken $secureTokenService
     * @param ProfileManager $profileManager
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        QuoteSessionInterface $session,
        Generic $sessionTransparent,
        SecureToken $secureTokenService,
        ProfileManager $profileManager,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->session = $session;
        $this->sessionTransparent = $sessionTransparent;
        if ($moduleManager->isEnabled("Magento_Paypal")) {
            $this->transparent = $objectManager->get("Magento\Paypal\Model\Payflow\Transparent");
        }
        $this->secureTokenService = $secureTokenService;
        $this->profileManager = $profileManager;
        parent::__construct($context);
    }

    /**
     * @return $this|Json
     */
    public function execute()
    {
        if ($this->getRequest()->getParam(SummaryInsertForm::FORM_DATA_KEY, 0)) {
            $profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
            $object = $profile;
        } else {
            /** @var DataObject $object */
            $object = $this->session->getFirstQuote();

            if (!$object || !$object instanceof Quote) {
                return $this->getErrorResponse();
            }
            $this->sessionTransparent->setQuoteId($object->getId());
        }

        try {
            $token = $this->secureTokenService->requestToken($object);

            if (!$token->getData('securetoken')) {
                throw new \LogicException();
            }
            $this->sessionTransparent->setSecureToken($token->getData('securetoken'));

            return $this->resultJsonFactory->create()->setData(
                [
                    $this->transparent->getCode() => ['fields' => $token->getData()],
                    'success' => true,
                    'error' => false
                ]
            );
        } catch (\Exception $e) {
            return $this->getErrorResponse();
        }
    }


    /**
     * @return Json
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
