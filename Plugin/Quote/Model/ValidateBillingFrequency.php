<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model;

use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Service\Serializer;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Model\Quote\ValidationState;

/**
 * Class ValidateBillingFrequency - validates the product billing frequencies
 */
class ValidateBillingFrequency
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var ProductBillingFrequencyRepositoryInterface
     */
    private $productBillingFrequencyRepository;

    /**
     * @var ValidationState
     */
    private $validationState;

    /**
     * ValidateBillingFrequency constructor.
     * @param Serializer $serializer
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository
     * @param ValidationState $validationState
     */
    public function __construct(
        Serializer $serializer,
        ProductBillingFrequencyRepositoryInterface $productBillingFrequencyRepository,
        ValidationState $validationState
    ) {
        $this->validationState = $validationState;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->serializer = $serializer;
    }

    /**
     * @param Quote $subject
     * @param $result
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterValidateMinimumAmount(
        Quote $subject,
        $result
    ) {
        if ($result) {
            foreach ($subject->getAllVisibleItems() as $item) {
                $option = $item->getOptionByCode('subscription');
                if (null === $option) {
                    continue;
                } else {
                    $billingFrequency = $this->serializer->unserialize($option->getValue())['billing_frequency'];
                    $productBillingFrequencies = $this->productBillingFrequencyRepository
                        ->getListByProductId($option->getProductId());
                    $validFrequency = false;
                    foreach ($productBillingFrequencies->getitems() as $productBillingFrequency) {
                        if ($productBillingFrequency->getBillingFrequencyId() == $billingFrequency) {
                            $validFrequency = true;
                        }
                    }
                    if (!$validFrequency) {
                        $this->validationState->setProductFrequencyValidated($validFrequency);
                        return $validFrequency;
                    }
                }
            }
        }
        return $result;
    }
}
