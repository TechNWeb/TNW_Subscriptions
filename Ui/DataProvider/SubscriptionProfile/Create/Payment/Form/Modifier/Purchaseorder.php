<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\OfflinePayments\Model\Purchaseorder as PurchaseorderPayment;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Class Purchaseorder
 * @package TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier
 */
class Purchaseorder extends Base implements PaymentModifierInterface
{
    const PO_NUMBER = 'po_number';
    const SORT_ORDER = 12;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var PurchaseorderPayment
     */
    private $purchaseorderPayment;

    /**
     * Purchaseorder constructor.
     * @param Config $config
     * @param QuoteSessionInterface $session
     * @param SubscriptionProfileRepository $profileRepository
     * @param OrderRelationManager $relationManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param Context $context
     * @param PurchaseorderPayment $purchaseorderPayment
     */
    public function __construct(
        Config $config,
        QuoteSessionInterface $session,
        SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        Context $context,
        PurchaseorderPayment $purchaseorderPayment
    ) {
        $this->context = $context;
        $this->purchaseorderPayment = $purchaseorderPayment;
        parent::__construct($config, $session, $profileRepository, $relationManager, $cartRepository);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentCode()
    {
        return PurchaseorderPayment::PAYMENT_METHOD_PURCHASEORDER_CODE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentTitle()
    {
        return $this->purchaseorderPayment->getTitle();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalFields()
    {
        $result = [];
        $result[static::PO_NUMBER] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => 'input',
                        'componentType' => 'field',
                        'elementTmpl' => 'TNW_Subscriptions/form/element/input',
                        'label' => __('Purchase Order Number:'),
                        'additionalClasses' => 'admin__field-wide'
                    ],
                ],
            ],
        ];
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalConfig()
    {
        return [
            'options' => [
                'gateway' => $this->getPaymentCode(),
                'formName' => $this->getPaymentFormName()
            ]
        ];
    }
}
