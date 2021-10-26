<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\OfflinePayments\Model\Banktransfer as BanktransferPayment;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Class BankTransfer -modifier
 */
class BankTransfer extends Base implements PaymentModifierInterface
{
    /**
     * Sort order value
     */
    const SORT_ORDER = 11;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var BanktransferPayment
     */
    private $banktransferPayment;

    /**
     * BankTransfer constructor.
     * @param Config $config
     * @param QuoteSessionInterface $session
     * @param SubscriptionProfileRepository $profileRepository
     * @param OrderRelationManager $relationManager
     * @param CartRepositoryInterface $cartRepository
     * @param Context $context
     * @param BanktransferPayment $banktransferPayment
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        QuoteSessionInterface $session,
        SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        CartRepositoryInterface $cartRepository,
        Context $context,
        BanktransferPayment $banktransferPayment,
        StoreManagerInterface $storeManager
    ) {
        $this->context = $context;
        $this->banktransferPayment = $banktransferPayment;
        parent::__construct($config, $session, $profileRepository, $relationManager, $cartRepository, $storeManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentCode()
    {
        return BanktransferPayment::PAYMENT_METHOD_BANKTRANSFER_CODE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentTitle()
    {
        return $this->banktransferPayment->getTitle();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalFields()
    {
        $result = [];
        $instructions = $this->banktransferPayment->getInstructions();
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
