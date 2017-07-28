<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Review\Form\Modifier;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;
use TNW\Subscriptions\Model\Context;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Framework\Locale\CurrencyInterface;

class ProductDetailsBase implements ModifierInterface
{
    /**#@+
     * Name of payment details fieldset.
     */
    const PAYMENT_DETAILS_FIELD_SET_NAME = 'payment_details';
    /**#@-*/

    /**
     * Help retrieve data from quotes.
     *
     * @var Create
     */
    private $create;

    /**
     * Quote payment.
     *
     * @var bool|ModelQuote\Payment
     */
    private $payment;

    /**
     * Store manager interface.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Provides access to currency config information.
     *
     * @var CurrencyInterface
     */
    private $localeCurrency;

    /**
     * @var Context
     */
    protected $context;

    /**
     * ProductDetailsBase constructor.
     * @param Create $create
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param CurrencyInterface $localeCurrency
     */
    public function __construct(
        Create $create,
        Context $context,
        StoreManagerInterface $storeManager,
        CurrencyInterface $localeCurrency
    ) {
        $this->create = $create;
        $this->context = $context;
        $this->storeManager = $storeManager;
        $this->localeCurrency = $localeCurrency;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_replace_recursive(
            $meta,
            $this->getPaymentFields()
        );

        return $meta;
    }

    /**
     * Returns payment details fields configuration.
     *
     * @return array
     */
    private function getPaymentFields()
    {
        $result = [
            static::PAYMENT_DETAILS_FIELD_SET_NAME => [
                'children' => $this->getChildren(),
            ],
        ];

        return $result;
    }

    /**
     * Returns array of payment details fields configuration.
     *
     * @return array
     */
    private function getChildren()
    {
        $result['payment_method'] = $this->getPaymentNameField();
        $result = array_merge_recursive(
            $result,
            $this->getAdditionalFields()
        );
        $result['initial_payment'] = $this->getInitialPaymentField();

        return $result;
    }

    /**
     * Returns Payment name field configuration.
     *
     * @return array
     */
    private function getPaymentNameField()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Payment Method'),
                        'visible' => true,
                        'dataScope' => 'payment_method',
                        'componentType' => Field::NAME,
                        'formElement' => 'input',
                        'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                        'value' => $this->getPaymentName(),
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns additional fields configuration (depends on paument method).
     *
     * @return array
     */
    protected function getAdditionalFields()
    {
        return [];
    }

    /**
     * Returns Quotes grand total field configuration.
     *
     * @return array
     */
    private function getInitialPaymentField()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Initial Payment'),
                        'visible' => true,
                        'dataScope' => 'initial_payment',
                        'componentType' => Field::NAME,
                        'formElement' => 'input',
                        'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                        'value' => $this->formatPrice($this->create->getSubQuotesGrandTotal()),
                        'addSymbol' => $this->getCurrencySymbol(),
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns quote payment or false if it doesn't exist.
     *
     * @return bool|ModelQuote\Payment
     */
    protected function getPayment()
    {
        if (!$this->payment) {
            $this->payment = $this->create->getPayment();
        }

        return $this->payment;
    }

    /**
     * Returns payment model interface or false if it doesn't exist.
     *
     * @return bool|\Magento\Payment\Model\MethodInterface
     */
    protected function getPaymentMethodInstance()
    {
        $return = false;
        if ($this->getPayment()) {
            $return = $this->getPayment()->getMethodInstance();
        }

        return $return;
    }

    /**
     * Returns payment name to show on admin.
     *
     * @return string
     */
    private function getPaymentName()
    {
        $name = '';
        $paymentMethodInstance = $this->getPaymentMethodInstance();
        if ($paymentMethodInstance) {
            $name = $this->context->getEscaper()->escapeHtml($paymentMethodInstance->getTitle());
        }

        return $name;
    }

    /**
     * Get currency symbol.
     *
     * @return string
     */
    private function getCurrencySymbol()
    {
        return $this->storeManager->getStore()->getBaseCurrency()->getCurrencySymbol();
    }

    /**
     * Format price according to the locale of the currency.
     *
     * @param mixed $value
     * @return string
     */
    protected function formatPrice($value)
    {
        if (!is_numeric($value)) {
            return null;
        }

        /** @var \Magento\Framework\Currency $currency */
        $currency = $this->getCurrency();
        $value = $currency->toCurrency($value, ['display' => \Magento\Framework\Currency::NO_SYMBOL]);

        return $value;
    }

    /**
     * Get currency object.
     *
     * @return \Magento\Framework\Currency
     */
    private function getCurrency()
    {
        $store = $this->storeManager->getStore();
        $currency = $this->localeCurrency->getCurrency($store->getBaseCurrencyCode());

        return $currency;
    }
}
