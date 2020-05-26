<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SecureAcceptance;

/**
 * Class TokenRequest
 * @package TNW\Subscriptions\Controller\Adminhtml\SecureAcceptance
 */
class TokenRequest extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var \Magento\Payment\Gateway\Command\Result\ArrayResultFactory
     */
    protected $resultFactory;

    /**
     * @var \TNW\Subscriptions\Model\Payment\Cybersource\TokenRequestDataBuilder
     */
    private $tokenRequestDataBuilder;

    private $quoteSession;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Payment\Gateway\Command\Result\ArrayResultFactory $resultFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \TNW\Subscriptions\Model\Payment\Cybersource\TokenRequestDataBuilder $tokenRequestDataBuilder,
        \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession
    ) {
        parent::__construct($context);
        $this->quoteSession = $quoteSession;
        $this->resultFactory = $resultFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->tokenRequestDataBuilder = $tokenRequestDataBuilder;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $billingAddress = [];
        $firstQuote = null;
        foreach ($this->quoteSession->getSubQuotes() as $quote) {
            $billingAddress = $quote->getBillingAddress();
            $firstQuote = $quote;
            break;
        }
        try {
            $commandResult = $this->resultFactory->create(['array' => $this->tokenRequestDataBuilder->build(
                [
                    'order_id' => $firstQuote->getId(),
                    'session_id' => $this->_session->getId(),
                    'card_type' =>  $this->getRequest()->getParam('cc_type'),
                    'currency' => $firstQuote->getQuoteCurrencyCode(),
                    'billing_address' => [
                        'firstname' => $billingAddress->getFirstname(),
                        'lastname' => $billingAddress->getLastname(),
                        'email' => $billingAddress->getEmail(),
                        'country_id' => $billingAddress->getCountryId(),
                        'city' => $billingAddress->getCity(),
                        'region_code' => $billingAddress->getRegionCode(),
                        'street_line_1' => implode("\n", $billingAddress->getStreet()),
                        'postcode' => $billingAddress->getPostcode(),
                    ]
                ]
            )]);
            $requestFields = $commandResult->get();
            $this->_session->setData('chcybersource_security_key', $requestFields['transaction_uuid']);
            $result->setData(
                [
                    'success' => true,
                    \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE => ['fields' => $requestFields]
                ]
            );

        } catch (\Exception $e) {
            $result->setData(['error' => __('Unable to build Token request')]);
        }

        return $result;
    }
}
