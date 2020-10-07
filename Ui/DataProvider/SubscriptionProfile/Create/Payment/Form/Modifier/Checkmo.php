<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\OfflinePayments\Model\Checkmo as CheckmoPayment;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Form modifier to display payment method Checkmo.
 */
class Checkmo extends Base implements PaymentModifierInterface
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
     * @param Config $config
     * @param QuoteSessionInterface $session
     * @param SubscriptionProfileRepository $profileRepository
     * @param OrderRelationManager $relationManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param Context $context
     * @param CheckmoPayment $checkmoPayment
     */
    public function __construct(
        Config $config,
        QuoteSessionInterface $session,
        SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        Context $context,
        CheckmoPayment $checkmoPayment
    ) {
        $this->context = $context;
        $this->checkmoPayment = $checkmoPayment;
        parent::__construct($config, $session, $profileRepository, $relationManager, $cartRepository);
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
            'listens'=> $this->getListens(),
            'options' => [
                'gateway' => $this->getPaymentCode(),
                'formName' => $this->getPaymentFormName()
            ]
        ];
    }
}
