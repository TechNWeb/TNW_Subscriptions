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

    /**
     * TokenRequest constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Payment\Gateway\Command\Result\ArrayResultFactory $resultFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \TNW\Subscriptions\Model\Payment\Cybersource\TokenRequestDataBuilder $tokenRequestDataBuilder
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Payment\Gateway\Command\Result\ArrayResultFactory $resultFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \TNW\Subscriptions\Model\Payment\Cybersource\TokenRequestDataBuilder $tokenRequestDataBuilder
    ) {
        parent::__construct($context);
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

        try {
            $commandResult = $this->resultFactory->create(['array' => $this->tokenRequestDataBuilder->build(
                [
                    'order_id' => '24',
                    'session_id' => $this->_session->getSessionId(),
                    'card_type' =>  $this->getRequest()->getParam('cc_type'),
                    'currency' => 'USD',
                    'billing_address' => [
                        'firstname' => 'aloha',
                        'lastname' => 'aloha2',
                        'email' => 'aloha@gmail.com',
                        'country_id' => 'US',
                        'city' => 'test',
                        'region_code' => 'CA',
                        'street_line_1' => 'gwegw',
                        'postcode' => '90230',
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
