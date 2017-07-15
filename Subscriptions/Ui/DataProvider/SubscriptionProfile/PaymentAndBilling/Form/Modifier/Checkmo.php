<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\PaymentAndBilling\Form\Modifier;

use Magento\OfflinePayments\Model\Checkmo as CheckmoPayment;
use TNW\Subscriptions\Model\Context;

/**
 * Form modifier to display payment method Checkmo.
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
     * @var Context
     */
    private $context;

    /**
     * @var CheckmoPayment
     */
    private $checkmoPayment;

    /**
     * Checkmo constructor.
     * @param Context $context
     * @param CheckmoPayment $checkmoPayment
     */
    public function __construct(
        Context $context,
        CheckmoPayment $checkmoPayment
    ) {
        $this->context = $context;
        $this->checkmoPayment = $checkmoPayment;
    }


    /**
     * @return string
     */
    protected function getPaymentCode()
    {
        return CheckmoPayment::PAYMENT_METHOD_CHECKMO_CODE;
    }

    /**
     * @return string
     */
    protected function getPaymentTitle()
    {
        return $this->checkmoPayment->getTitle();
    }

    protected function getAdditionalFields()
    {
        return [
            static::ADDITIONAL_FIELD_MAILING_ADDRESS => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'input',
                            'componentType' => 'field',
                            'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                            'value' => $this->context->getEscaper()->escapeHtml(
                                $this->checkmoPayment->getMailingAddress()
                            ),
                            'label' => __('Send Check to:'),
                            'additionalClasses' => 'admin__field-wide'
                        ],
                    ],
                ],
            ],
            static::ADDITIONAL_FIELD_PAYABLE_TO => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'input',
                            'componentType' => 'field',
                            'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                            'value' => $this->context->getEscaper()->escapeHtml(
                                $this->checkmoPayment->getPayableTo()
                            ),
                            'label' => __('Make Check payable to:'),
                            'additionalClasses' => 'admin__field-wide'
                        ],
                    ],
                ],
            ]
        ];
    }
}
