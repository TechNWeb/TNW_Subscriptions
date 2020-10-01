<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Validator - paypal responses validator
 */
class Validator
{
    /**
     * @var mixed
     */
    private $responseValidator;

    /**
     * @var mixed
     */
    private $payflowFacade;

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * Validator constructor.
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     * @param ValidationResultFactory $validationResultFactory
     */
    public function __construct(
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        \TNW\Subscriptions\Model\Payment\Paypal\ValidationResultFactory $validationResultFactory
    ) {
        if ($moduleManager->isEnabled("Magento_Paypal")) {
            $this->payflowFacade = $objectManager->get(\Magento\Paypal\Model\Payflow\Transparent::class);
            $this->responseValidator = $objectManager
                ->get(\Magento\Paypal\Model\Payflow\Service\Response\Validator\ResponseValidator::class);
        }
        $this->validationResultFactory = $validationResultFactory;
    }

    /**
     * @param $data
     * @return ValidationResult
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
