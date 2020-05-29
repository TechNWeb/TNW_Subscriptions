<?php

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\Summary;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Config;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context;

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
     * @var Context
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
     * Payment constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $paymentConfig
     * @param QuoteSessionInterface $session
     * @param Context $formContext
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $paymentConfig,
        QuoteSessionInterface $session,
        Context $formContext
    ) {
        $this->paymentConfig = $paymentConfig;
        $this->session = $session;
        $this->formContext = $formContext;
        $this->scopeConfig = $scopeConfig;
    }

    public function modifyData(array $data)
    {
        $data['new_subscription']['payment_method_content'] = $this->getPaymentMethodTitle();
        $data['new_subscription']['cc_type'] = $this->getCardTypeLabel();
        $data['new_subscription']['cc_last_4'] = $this->getCardLastFour();
        return $data;
    }

    public function modifyMeta(array $meta)
    {
        if ($ccTypeConfig = $this->getCardTypeConfig()) {
            $meta['payment_method']['children']['cc_type'] = $ccTypeConfig;
        }
        if ($ccLast4 = $this->getCardLastFourConfig()) {
            $meta['payment_method']['children']['cc_last_4'] = $ccLast4;
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
            $path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getSubQuote()->getStore()
        );
    }

    private function getPaymentAdditionalInfo()
    {
        $payment = $this->getPayment();
        return $payment->getAdditionalInformation();
    }

    /**
     * @return string|null
     */
    private function getCardLastFour()
    {
        return $this->getPayment()->getData('cc_last_4')
            ? 'XXXX-XXXX-XXXX-' . $this->getPayment()->getData('cc_last_4')
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
                        'label' => __('Credit Card Type')
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
                        'label' => __('Credit Card Number')
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
}
