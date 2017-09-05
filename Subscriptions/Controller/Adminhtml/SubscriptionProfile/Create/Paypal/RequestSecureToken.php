<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create\Paypal;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\Generic;
use TNW\Subscriptions\Model\Payment\Paypal\SecureToken;
use Magento\Paypal\Model\Payflow\Transparent;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Class RequestSecureToken
 */
class RequestSecureToken extends \Magento\Framework\App\Action\Action
{
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
     * @var Transparent
     */
    private $transparent;

    /**
     * RequestSecureToken constructor.
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param Generic $sessionTransparent
     * @param Transparent $transparent
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        QuoteSessionInterface $session,
        Generic $sessionTransparent,
        Transparent $transparent,
        SecureToken $secureTokenService
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->session = $session;
        $this->sessionTransparent = $sessionTransparent;
        $this->transparent = $transparent;
        $this->secureTokenService = $secureTokenService;
        parent::__construct($context);
    }


    public function execute()
    {
        /** @var Quote $quote */
        $quote = $this->session->getFirstQuote();

        if (!$quote || !$quote instanceof Quote) {
            return $this->getErrorResponse();
        }

        $this->sessionTransparent->setQuoteId($quote->getId());
        try {
            $token = $this->secureTokenService->requestToken($quote);
            if (!$token->getData('securetoken')) {
                throw new \LogicException();
            }

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
