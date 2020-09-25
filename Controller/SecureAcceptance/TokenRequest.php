<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\SecureAcceptance;

/**
 * Class TokenRequest - controller
 */
class TokenRequest extends \Magento\Framework\App\Action\Action
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
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Manager
     */
    private $manager;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * TokenRequest constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Payment\Gateway\Command\Result\ArrayResultFactory $resultFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \TNW\Subscriptions\Model\Payment\Cybersource\TokenRequestDataBuilder $tokenRequestDataBuilder
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager
     * @param \TNW\Subscriptions\Model\SubscriptionProfileRepository $profileRepository
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Payment\Gateway\Command\Result\ArrayResultFactory $resultFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \TNW\Subscriptions\Model\Payment\Cybersource\TokenRequestDataBuilder $tokenRequestDataBuilder,
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager,
        \TNW\Subscriptions\Model\SubscriptionProfileRepository $profileRepository
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->profileRepository = $profileRepository;
        $this->manager = $manager;
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
        $quote = null;
        if ($profileId = $this->getRequest()->getParam('profile_id')) {
            $quote = $this->manager->getTempQuote($this->profileRepository->getById($profileId));
            $billingAddress = $quote->getBillingAddress();
            try {
                $commandResult = $this->resultFactory->create(['array' => $this->tokenRequestDataBuilder->build(
                    [
                    'order_id' => $quote->getId(),
                    'session_id' => $this->customerSession->getSessionId(),
                    'card_type' => $this->getRequest()->getParam('cc_type'),
                    'currency' => $quote->getQuoteCurrencyCode(),
                    'billing_address' => [
                        'firstname' => $billingAddress->getFirstname(),
                        'lastname' => $billingAddress->getLastname(),
                        'email' => $billingAddress->getEmail(),
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
                $this->customerSession->setData('chcybersource_security_key', $requestFields['transaction_uuid']);
                $result->setData(
                    [
                    'success' => true,
                    \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE => ['fields' => $requestFields]
                    ]
                );

            } catch (\Exception $e) {
                $result->setData(['error' => __('Unable to build Token request')]);
            }
        }
        return $result;
    }
}
