<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\Summary;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\Source\ShippingMethods;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context;

/**
 * Class Shipping - modifier
 */
class Shipping implements ModifierInterface
{
    /**
     * @var QuoteSessionInterface
     */
    private $session;
    /**
     * @var ShippingMethods
     */
    private $shippingMethods;
    /**
     * @var Context
     */
    private $formContext;

    /**
     * Shipping constructor.
     * @param QuoteSessionInterface $session
     * @param ShippingMethods $shippingMethods
     * @param Context $formContext
     */
    public function __construct(
        QuoteSessionInterface $session,
        ShippingMethods $shippingMethods,
        Context $formContext
    ) {

        $this->session = $session;
        $this->shippingMethods = $shippingMethods;
        $this->formContext = $formContext;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $data['new_subscription']['shipping_method_content'] = $this->getShippingMethodAmount();
        return $data;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_merge_recursive(
            $meta,
            [
                'shipping_method' => [
                    'children' => [
                        'shipping_method_content' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'label' => $this->getShippingMethodLabel()
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );
        return $meta;
    }

    /**
     * Get current shipping method title
     * @return string|null
     */
    private function getShippingMethodLabel()
    {
        if ($this->getIsVirtual()) {
            return __('Shipping not required.');
        }
        $subQuotes = $this->formContext->getSession()->getSubQuotes();
        foreach ($subQuotes as $subQuote) {
            if ($subQuote->getIsVirtual()) {
                continue;
            }
            $this->shippingMethods->setQuote($subQuote);
            if ($methodTitle = $this->shippingMethods->getCurrentMethodLabel(false)) {
                return $methodTitle;
            }
        }
        return null;
    }

    /**
     * Get shipping amount from all quotes
     * @return float|null
     */
    private function getShippingMethodAmount()
    {
        $amount = 0;
        if ($this->getIsVirtual()) {
            return null;
        }
        $subQuotes = $this->formContext->getSession()->getSubQuotes();
        foreach ($subQuotes as $subQuote) {
            if ($subQuote->getIsVirtual()) {
                continue;
            }
            $this->shippingMethods->setQuote($subQuote);
            $method = $subQuote->getShippingAddress()->getShippingMethod();
            $rates = $this->shippingMethods->getShippingRates();
            foreach ($rates as $rate) {
                if ($rate->getCode() === $method) {
                    $amount += (float)$rate->getPrice();
                }
            }
        }
        return $this->shippingMethods->getShippingPrice($amount, false);
    }

    /**
     * Check if all quotes qre virtual
     * @return bool
     */
    private function getIsVirtual()
    {
        $isVirtual = true;
        $subQuotes = $this->formContext->getSession()->getSubQuotes();
        foreach ($subQuotes as $subQuote) {
            $isVirtual = $subQuote->getIsVirtual() ? $isVirtual : false;
        }
        return $isVirtual;
    }
}
