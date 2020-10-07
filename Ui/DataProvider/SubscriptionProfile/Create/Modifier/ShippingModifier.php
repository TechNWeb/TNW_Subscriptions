<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier;

use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\Source\ShippingMethods;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context;

/**
 * Class ShippingModifier - modifier
 */
class ShippingModifier implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    const SHIPPING_FIELDSET = 'shipping_methods';

    const SHIPPING_SPECIFIC_FIELDSET = 'specific_shipping';

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
     * ShippingModifier constructor.
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
     * @inheritDoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_merge_recursive(
            $meta,
            [
                static::SHIPPING_FIELDSET => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'visible' => !$this->getIsVirtual()
                            ]
                        ]
                    ],
                    'children' => [
                        static::SHIPPING_SPECIFIC_FIELDSET => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'options' => $this->getShippingMethodsData()
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
     * Get available shipping methods for all quotes.
     * @return array
     */
    private function getShippingMethodsData()
    {
        $options = [];
        $subQuotes = $this->formContext->getSession()->getSubQuotes();
        foreach ($subQuotes as $subQuote) {
            $this->shippingMethods->setQuote($subQuote);
            $rates = $this->shippingMethods->getShippingRates();
            foreach ($rates as $rate) {
                if (!empty($options[$rate->getCode()])) {
                    continue;
                }
                if (in_array($rate->getMethod(), $this->shippingMethods->getDontCostDependedMethodsCodes())) {
                    $options[$rate->getCode()] = [
                        'value' => $rate->getCode(),
                        'label' => $rate->getCarrierTitle() . ' (' . $rate->getMethodTitle() . ')' . ' - '
                            . $this->shippingMethods->getShippingPrice($rate->getPrice(), false) . ' (per item)'
                    ];
                } else {
                    $options[$rate->getCode()] = [
                        'value' => $rate->getCode(),
                        'label' => $rate->getCarrierTitle() . ' (' . $rate->getMethodTitle() . ')'
                    ];
                }
            }
        }
        return array_values($options);
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
