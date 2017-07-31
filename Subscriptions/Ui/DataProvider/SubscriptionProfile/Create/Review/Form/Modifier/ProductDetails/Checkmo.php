<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Review\Form\Modifier\ProductDetails;

/**
 * Form modifier to display payment details for payment method Checkmo.
 */
class Checkmo extends Base
{
    /**#@+
     * Checkmo additional field names.
     */
    const ADDITIONAL_FIELD_MAILING_ADDRESS = 'mailing_address';
    const ADDITIONAL_FIELD_PAYABLE_TO = 'payable_to';
    /**#@-*/

    /**
     * @inheritdoc
     */
    protected function getAdditionalFields()
    {
        $result = [];
        $mailingAddress = null;
        $payableTo = null;

        if ($this->getPaymentMethodInstance()) {
            $mailingAddress = $this->getPaymentMethodInstance()->getMailingAddress();
            $payableTo = $this->getPaymentMethodInstance()->getPayableTo();
        }

        if ($mailingAddress) {
            $result[static::ADDITIONAL_FIELD_MAILING_ADDRESS] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'input',
                            'componentType' => 'field',
                            'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                            'value' => $this->context->getEscaper()->escapeHtml($mailingAddress),
                            'label' => __('Send Check to:'),
                        ],
                    ],
                ],
            ];
        }

        if ($mailingAddress) {
            $result[static::ADDITIONAL_FIELD_PAYABLE_TO] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'input',
                            'componentType' => 'field',
                            'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                            'value' => $this->context->getEscaper()->escapeHtml($payableTo),
                            'label' => __('Make Check payable to:'),
                        ],
                    ],
                ],
            ];
        }

        return $result;
    }
}
