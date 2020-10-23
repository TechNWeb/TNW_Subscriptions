<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\OfflinePayments\Model\Cashondelivery as CashondeliveryPayment;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Class Cashondelivery - ui modifier
 */
class Cashondelivery extends Base implements PaymentModifierInterface
{
    /**
     * Sort order value
     */
    const SORT_ORDER = 13;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var CashondeliveryPayment
     */
    private $cashondeliveryPayment;

    /**
     * BankTransfer constructor.
     * @param Config $config
     * @param QuoteSessionInterface $session
     * @param SubscriptionProfileRepository $profileRepository
     * @param OrderRelationManager $relationManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param Context $context
     * @param CashondeliveryPayment $cashondeliveryPayment
     */
    public function __construct(
        Config $config,
        QuoteSessionInterface $session,
        SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        Context $context,
        CashondeliveryPayment $cashondeliveryPayment
    ) {
        $this->context = $context;
        $this->cashondeliveryPayment = $cashondeliveryPayment;
        parent::__construct($config, $session, $profileRepository, $relationManager, $cartRepository);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentCode()
    {
        return CashondeliveryPayment::PAYMENT_METHOD_CASHONDELIVERY_CODE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentTitle()
    {
        return $this->cashondeliveryPayment->getTitle();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalFields()
    {
        $result = [];
        $instructions = $this->cashondeliveryPayment->getInstructions();
        if ($instructions) {
            $result['payment_instructions'] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'input',
                            'componentType' => 'field',
                            'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                            'value' => $this->context->getEscaper()->escapeHtml(
                                $instructions
                            ),
                            'label' => __('Payment Instructions:'),
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
