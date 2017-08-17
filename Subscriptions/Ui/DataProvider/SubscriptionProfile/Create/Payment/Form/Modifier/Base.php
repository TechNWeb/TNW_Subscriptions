<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;

/**
 * Base form modifier to display payment method.
 */
class Base implements ModifierInterface
{
    /**
     * @var \TNW\Subscriptions\Model\Config
     */
    private $config;

    /**
     * @param \TNW\Subscriptions\Model\Config $config
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config
    ) {
        $this->config = $config;
    }

    /**#@+
     * Name of payment information fieldset.
     */
    const PAYMENT_INFORMATION_FIELD_SET_NAME = 'payment_information';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        if (in_array($this->getPaymentCode(), $this->config->getAvailablePaymentsList())) {
            $meta = array_replace_recursive(
                $meta,
                $this->getPaymentFields()
            );
        }

        return $meta;
    }

    /**
     * Returns payment method code.
     *
     * @return string
     */
    protected function getPaymentCode()
    {
        return '';
    }

    /**
     * Returns payment method title.
     *
     * @return string
     */
    protected function getPaymentTitle()
    {
        return '';
    }

    /**
     * Returns additional fields for payment method (if payment method have it).
     *
     * @return array
     */
    protected function getAdditionalFields()
    {
        return [];
    }

    /**
     * Returns metadata for payment method fieldset.
     *
     * @return array
     */
    private function getPaymentFields()
    {
        $additionalConfig = $this->getAdditionalConfig();

        return [
            static::PAYMENT_INFORMATION_FIELD_SET_NAME => [
                'children' => [
                    $this->getPaymentCode() => [
                        'children' => $this->getChildren(),
                        'arguments' => [
                            'data' => [
                                'config' => array_merge(
                                    [
                                        'label' => false,
                                        'collapsible' => false,
                                        'visible' => true,
                                        'opened' => true,
                                        'dataScope' => $this->getPaymentCode(),
                                        'componentType' => Fieldset::NAME,
                                        'additionalClasses' => 'fieldset-wrapper-title'
                                    ],
                                    $additionalConfig
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns metadata for payment method choose field.
     *
     * @return array
     */
    private function getField()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => Checkbox::NAME,
                        'componentType' => Field::NAME,
                        'prefer' => 'radio',
                        'description' => $this->getPaymentTitle(),
                        'dataScope' => 'method',
                        'component' => 'TNW_Subscriptions/js/components/extended-checkbox',
                        'parentContainer' => static::PAYMENT_INFORMATION_FIELD_SET_NAME,
                        'parentSelections' => static::PAYMENT_INFORMATION_FIELD_SET_NAME,
                        'additionalClasses' => 'payment-checkbox',
                        'dataType' => 'number',
                        'valueMap' => [
                            'false' => '0',
                            'true' => '1',
                        ],
                        'default' => '1',
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns array of child elements.
     *
     * @return array
     */
    private function getChildren()
    {
        $result = [
            'method' => $this->getField(),
        ];
        $fieldsetName = $this->getFieldsetName();
        $checkBoxName = $fieldsetName . '.method';
        $result['additional_fields'] = [
            'children' => $this->getAdditionalFields(),
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Fieldset::NAME,
                        'label' => false,
                        'visible' => false,
                        'dataScope' => 'additional',
                        'additionalClasses' => 'payment-additional-fieldset',
                        'collapsible' => false,
                        'opened' => true,
                        'imports' => [
                            'visible' => $checkBoxName . ':checked'
                        ],
                        'exports' => [
                            'visible' => $fieldsetName . ':checked'
                        ],
                    ],
                ],
            ],
        ];

        return $result;
    }

    /**
     * Returns additional payment method fieldset config.
     *
     * @return array
     */
    protected function getAdditionalConfig()
    {
        return [];
    }

    /**
     * Returns full name of current fieldset.
     *
     * @return string
     */
    protected function getFieldsetName()
    {
        $fieldsetName = Payment::DATA_SCOPE_PAYMENT_FORM .
            '.' . Payment::DATA_SCOPE_PAYMENT_FORM .
            '.' . static::PAYMENT_INFORMATION_FIELD_SET_NAME
            . '.' . $this->getPaymentCode();

        return $fieldsetName;
    }
}
