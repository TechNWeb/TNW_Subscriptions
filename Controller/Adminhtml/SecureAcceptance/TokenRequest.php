<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SecureAcceptance;

/**
 * Class TokenRequest - controller
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

    /**
     * @var \TNW\Subscriptions\Model\QuoteSessionInterface
     */
    private $quoteSession;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Manager
     */
    private $manager;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * TokenRequest constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Payment\Gateway\Command\Result\ArrayResultFactory $resultFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \TNW\Subscriptions\Model\Payment\Cybersource\TokenRequestDataBuilder $tokenRequestDataBuilder
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager
     * @param \TNW\Subscriptions\Model\SubscriptionProfileRepository $profileRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Payment\Gateway\Command\Result\ArrayResultFactory $resultFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \TNW\Subscriptions\Model\Payment\Cybersource\TokenRequestDataBuilder $tokenRequestDataBuilder,
        \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession,
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager,
        \TNW\Subscriptions\Model\SubscriptionProfileRepository $profileRepository
    ) {
        parent::__construct($context);
        $this->profileRepository = $profileRepository;
        $this->manager = $manager;
        $this->quoteSession = $quoteSession;
        $this->resultFactory = $resultFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->tokenRequestDataBuilder = $tokenRequestDataBuilder;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $billingAddress = [];
        $quote = null;
        if ($profileId = $this->getRequest()->getParam('profile_id')) {
            $quote = $this->manager->getTempQuote($this->profileRepository->getById($profileId));
            $billingAddress = $quote->getBillingAddress();
        } else {
            foreach ($this->quoteSession->getSubQuotes() as $subQuotequote) {
                $billingAddress = $subQuotequote->getBillingAddress();
                $quote = $subQuotequote;
                break;
            }
        }
        if (!$billingAddress->getEmail() || !$billingAddress->getFirstname() || !$billingAddress->getLastname()) {
            $billingAddress = $quote->getShippingAddress();
        }
        try {
            $commandResult = $this->resultFactory->create(['array' => $this->tokenRequestDataBuilder->build(
                [
                    'order_id' => $quote->getId(),
                    'session_id' => $this->_session->getSessionId(),
                    'card_type' => $this->getRequest()->getParam('cc_type'),
                    'currency' => $quote->getQuoteCurrencyCode(),
                    'billing_address' => [
                        'firstname' => $billingAddress->getFirstname(),
                        'lastname' => $billingAddress->getLastname(),
                        'email' => $billingAddress->getEmail() ?: $this->quoteSession->getCustomerEmail(),
                        'country_id' => $billingAddress->getCountryId(),
                        'city' => $billingAddress->getCity(),
                        'region_code' => $billingAddress->getRegionCode(),
                        'street_line_1' => $billingAddress->getStreetLine(1),
                        'street_line_2' => $billingAddress->getStreetLine(2),
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
