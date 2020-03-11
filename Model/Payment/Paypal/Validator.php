<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

/**
 * Class Validator
 * @package TNW\Subscriptions\Model\Payment\Paypal
 */
class Validator
{
    /**
     * @var \Magento\Paypal\Model\Payflow\Service\Response\Validator\ResponseValidator
     */
    private $responseValidator;

    /**
     * @var \Magento\Paypal\Model\Payflow\Transparent
     */
    private $payflowFacade;

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * Validator constructor.
     * @param \Magento\Paypal\Model\Payflow\Service\Response\Validator\ResponseValidator $responseValidator
     * @param \Magento\Paypal\Model\Payflow\Transparent $payflowFacade
     * @param ValidationResultFactory $validationResultFactory
     */
    public function __construct(
        \Magento\Paypal\Model\Payflow\Service\Response\Validator\ResponseValidator $responseValidator,
        \Magento\Paypal\Model\Payflow\Transparent $payflowFacade,
        \TNW\Subscriptions\Model\Payment\Paypal\ValidationResultFactory $validationResultFactory
    ) {
        $this->payflowFacade = $payflowFacade;
        $this->responseValidator = $responseValidator;
        $this->validationResultFactory = $validationResultFactory;
    }

    /**
     * @param $data
     * @return bool
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function validate($data)
    {
        $response = $data['response'];
        $this->payflowFacade->processErrors($response);
        $result = $this->validationResultFactory->create();
        try {
            $this->payflowFacade->getResponceValidator()->validate($response, $this->payflowFacade);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $result->setError(1, $exception->getMessage());
        }
        return $result;
    }
}
