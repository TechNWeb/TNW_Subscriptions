<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\Summary;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Config;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterfaceFactory;
use Magento\Quote\Model\Cart\TotalsConverter;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context as FormContext;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class Payment - modifier
 */
class Payment implements ModifierInterface
{
    /**
     * @var Config
     */
    private $paymentConfig;

    /**
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @var FormContext
     */
    private $formContext;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $subQuote;

    /**
     * @var CartTotalRepositoryInterface
     */
    private $quoteTotalRepository;

    /**
     * @var TotalsInterfaceFactory
     */
    private $totalsFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var TotalsConverter
     */
    private $totalsConverter;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Payment constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $paymentConfig
     * @param QuoteSessionInterface $session
     * @param CartTotalRepositoryInterface $quoteTotalRepository
     * @param TotalsInterfaceFactory $totalsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param TotalsConverter $totalsConverter
     * @param FormContext $formContext
     * @param Context $context
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $paymentConfig,
        QuoteSessionInterface $session,
        CartTotalRepositoryInterface $quoteTotalRepository,
        TotalsInterfaceFactory $totalsFactory,
        DataObjectHelper $dataObjectHelper,
        TotalsConverter $totalsConverter,
        FormContext $formContext,
        Context $context,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->paymentConfig = $paymentConfig;
        $this->session = $session;
        $this->formContext = $formContext;
        $this->scopeConfig = $scopeConfig;
        $this->quoteTotalRepository = $quoteTotalRepository;
        $this->totalsFactory = $totalsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->totalsConverter = $totalsConverter;
        $this->context = $context;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $data['new_subscription']['payment_method_content'] = $this->getPaymentMethodTitle();
        $data['new_subscription'][OrderPaymentInterface::CC_TYPE] = $this->getCardTypeLabel();
        $data['new_subscription'][OrderPaymentInterface::CC_LAST_4] = $this->getCardLastFour();
        foreach ($this->getTotalsData() as $key => $total) {
            $storeId = $this->subQuote->getStoreId();
            if ($storeId) {
                $storeCurrency = $this->priceCurrency->getCurrency($storeId);
                $data['new_subscription'][$key] = $storeCurrency->format(
                    $total['value'],
                    false,
                    false
                );
            } else {
                $data['new_subscription'][$key] =
                    $this->context->getPriceCurrency()->convertAndFormat((float)$total['value'], false);
            }
        }
        return $data;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $meta['payment_method']['children'] = $this->getTotalsConfig();
        if ($ccTypeConfig = $this->getCardTypeConfig()) {
            $meta['payment_method']['children'][OrderPaymentInterface::CC_TYPE] = $ccTypeConfig;
        }
        if ($ccLast4 = $this->getCardLastFourConfig()) {
            $meta['payment_method']['children'][OrderPaymentInterface::CC_LAST_4] = $ccLast4;
        }
        return $meta;
    }

    /**
     * @return string
     */
    private function getPaymentMethodTitle()
    {
        $method = $this->getPayment()->getMethod();
        $path = 'payment/' . $method . '/title';
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getSubQuote()->getStore()
        );
    }

    /**
     * @return string|null
     */
    private function getCardLastFour()
    {
        return $this->getPayment()->getData(OrderPaymentInterface::CC_LAST_4)
            ? 'XXXX-XXXX-XXXX-' . $this->getPayment()->getData(OrderPaymentInterface::CC_LAST_4)
            : null;
    }

    /**
     * @return string|null
     */
    private function getCardTypeLabel()
    {
        if (empty($this->getPayment()->getCcType())) {
            return null;
        }
        $ccTypes = $this->paymentConfig->getCcTypes();
        foreach ($ccTypes as $key => $label) {
            if ($key == $this->getPayment()->getCcType()) {
                return $label;
            }
        }
        return null;
    }

    /**
     * @return array|null
     */
    private function getCardTypeConfig()
    {
        if (empty($this->getCardTypeLabel())) {
            return null;
        }
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => \Magento\Ui\Component\Form\Field::NAME,
                        'formElement' => \Magento\Ui\Component\Form\Element\Input::NAME,
                        'dataType' => \Magento\Ui\Component\Form\Element\DataType\Text::NAME,
                        'elementTmpl' => 'ui/form/element/text',
                        'label' => __('Credit Card Type'),
                        'sortOrder' => 20
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array|null
     */
    private function getCardLastFourConfig()
    {
        if (empty($this->getCardLastFour())) {
            return null;
        }
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => \Magento\Ui\Component\Form\Field::NAME,
                        'formElement' => \Magento\Ui\Component\Form\Element\Input::NAME,
                        'dataType' => \Magento\Ui\Component\Form\Element\DataType\Text::NAME,
                        'elementTmpl' => 'ui/form/element/text',
                        'label' => __('Credit Card Number'),
                        'sortOrder' => 30
                    ]
                ]
            ]
        ];
    }

    /**
     * Get first quote from context
     * @return \Magento\Quote\Model\Quote
     */
    private function getSubQuote()
    {
        if (!$this->subQuote) {
            $subQuotes = $this->formContext->getSession()->getSubQuotes();
            $this->subQuote = reset($subQuotes);
        }
        return $this->subQuote;
    }

    /**
     * Get payment from quote
     * @return \Magento\Quote\Model\Quote\Payment
     */
    private function getPayment()
    {
        return $this->getSubQuote()->getPayment();
    }

    /**
     * Get totals data. Merge amounts from all available quotes.
     * @return array
     */
    private function getTotalsData()
    {
        $totalsData = [];
        foreach ($this->formContext->getSession()->getSubQuotes() as $quote) {
            if ($quote->isVirtual()) {
                $addressTotals = $quote->getBillingAddress()->getTotals();
            } else {
                $addressTotals = $quote->getShippingAddress()->getTotals();
            }
            $calculatedTotals = $this->totalsConverter->process($addressTotals);
            /** @var \Magento\Quote\Model\Cart\TotalSegment $total */
            foreach ($calculatedTotals as $total) {
                if (empty($totalsData[$total->getCode()])) {
                    $totalsData[$total->getCode()] = $total->toArray();
                } else {
                    $totalsData[$total->getCode()]['value'] =
                        (float)$totalsData[$total->getCode()]['value'] + (float)$total->getValue();
                    $totalsData[$total->getCode()]['title'] = empty($totalsData[$total->getCode()]['title'])
                        ? $total->getTitle()
                        : $totalsData[$total->getCode()]['title'];
                }
            }
        }
        return $totalsData;
    }

    /**
     * Get totals elements config
     * @return array
     */
    private function getTotalsConfig()
    {
        $config = [];
        $sortOrder = 50;
        foreach ($this->getTotalsData() as $key => $segment) {
            if (($key !== 'shipping' && $key !== 'tax') && empty($segment['value'])) {
                continue;
            }
            $config[$key] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => \Magento\Ui\Component\Form\Field::NAME,
                            'formElement' => \Magento\Ui\Component\Form\Element\Input::NAME,
                            'dataType' => \Magento\Ui\Component\Form\Element\DataType\Text::NAME,
                            'elementTmpl' => 'ui/form/element/text',
                            'label' => $segment['title'],
                            'sortOrder' => $sortOrder,
                            'additionalClasses' => 'total_' . $key,
                            'visible' => !empty($segment['title'])
                        ]
                    ]
                ]
            ];
            $sortOrder++;
        }
        return $config;
    }
}
