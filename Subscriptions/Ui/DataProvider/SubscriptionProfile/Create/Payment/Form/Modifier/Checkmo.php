<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\OfflinePayments\Model\Checkmo as CheckmoPayment;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;

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
    const SORT_ORDER = 10;
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
     * @param Config $config
     * @param QuoteSessionInterface $session
     * @param \TNW\Subscriptions\Model\SubscriptionProfileRepository $profileRepository
     * @param CheckmoPayment $checkmoPayment
     */
    public function __construct(
        Context $context,
        Config $config,
        QuoteSessionInterface $session,
        \TNW\Subscriptions\Model\SubscriptionProfileRepository $profileRepository,
        CheckmoPayment $checkmoPayment
    ) {
        $this->context = $context;
        $this->checkmoPayment = $checkmoPayment;

        parent::__construct($config, $session, $profileRepository);
    }


    /**
     * {@inheritdoc}
     */
    protected function getPaymentCode()
    {
        return CheckmoPayment::PAYMENT_METHOD_CHECKMO_CODE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentTitle()
    {
        return $this->checkmoPayment->getTitle();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalFields()
    {
        $result = [];
        $mailingAddress = $this->checkmoPayment->getMailingAddress();
        if ($mailingAddress) {
            $result[static::ADDITIONAL_FIELD_MAILING_ADDRESS] = [
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
            ];
        }

        $payableTo = $this->checkmoPayment->getPayableTo();
        if ($payableTo) {
            $result[static::ADDITIONAL_FIELD_PAYABLE_TO] = [
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
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalConfig()
    {
        return [
            'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/fieldset',
            'listens'=> $this->getListens(),
            'options' => [
                'gateway' => $this->getPaymentCode(),
                'formName' => $this->getPaymentFormName()
            ]
        ];
    }
}
