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
    /**#@+
     * Name of payment information fieldset.
     */
    const PAYMENT_INFORMATION_FIELD_SET_NAME = 'payment_information';
    const SORT_ORDER = 0;
    /**#@-*/

    /**
     * @var \TNW\Subscriptions\Model\Config
     */
    private $config;

    /**
     * Payment form name
     *
     * @var string
     */
    private $paymentFormName;

    /**
     * Additional namespace
     *
     * @var string
     */
    private $additionalNamespace;

    /**
     * Listens
     *
     * @var string
     */
    private $listens;

    /**
     * Profile id
     *
     * @var integer
     */
    private $profileId;

    /**
     * @param \TNW\Subscriptions\Model\Config $config
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config
    ) {
        $this->config = $config;
        $this->paymentFormName = Payment::DATA_SCOPE_PAYMENT_FORM;
        $this->listens = [
            'checked' => 'saveBilling'
        ];
    }

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
        if ($this->config->isPaymentMethodAvailableForSubscription($this->getPaymentCode())) {
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
     * @return array
     */
    protected function getListens()
    {
        return $this->listens;
    }

    /**
     * Sets listens
     *
     * @param $listens
     * @return $this
     */
    public function setListens($listens)
    {
        $this->listens = $listens;
        return $this;
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
                                        'additionalClasses' => 'fieldset-wrapper-title',
                                        'sortOrder' => $this::SORT_ORDER,
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
                        'default' => '0',
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
        $fieldsetName = $this->getPaymentFormName() .
            '.' . $this->getPaymentFormName() .
            ($this->additionalNamespace ? '.' . $this->additionalNamespace : '') .
            '.' . static::PAYMENT_INFORMATION_FIELD_SET_NAME
            . '.' . $this->getPaymentCode();

        return $fieldsetName;
    }

    /**
     * Sets payment form name
     *
     * @return string
     */
    public function setPaymentFormName($paymentFormName)
    {
        $this->paymentFormName = $paymentFormName;
        return $this;
    }

    /**
     * Returns payment form name
     *
     * @return string
     */
    public function getPaymentFormName()
    {
        return $this->paymentFormName;
    }

    /**
     * Sets additional namespace
     *
     * @return string
     */
    public function setAdditionalNamespace($additionalNamespace)
    {
        $this->additionalNamespace = $additionalNamespace;
        return $this;
    }

    /**
     * Returns profile id
     *
     * @return int
     */
    protected function getProfileId()
    {
        return $this->profileId;
    }

    /**
     * Sets profile id
     *
     * @param $profileId
     * @return $this
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;
        return $this;
    }
}
