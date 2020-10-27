<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;
use TNW\Subscriptions\Plugin\CyberSource\SecureAcceptance\Model\VaultPlugin;

/**
 * Save additional payment data processor.
 */
class AdditionalPayment extends Base
{
    /**
     * @var VaultPlugin
     */
    private $cybersourceActiveChecker;

    /**
     * AdditionalPayment constructor.
     * @param CreateProfile $createModel
     * @param QuoteSessionInterface $session
     * @param VaultPlugin $cybersourceActiveChecker
     */
    public function __construct(
        CreateProfile $createModel,
        QuoteSessionInterface $session,
        VaultPlugin $cybersourceActiveChecker
    ) {
        parent::__construct($createModel, $session);
        $this->cybersourceActiveChecker = $cybersourceActiveChecker;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $additionalData = [];
        $paymentData = !empty($data['payment']) ? $data['payment'] : [];
        if ($paymentData && empty($paymentData['undefined'])) {
            foreach ($paymentData as $code => $methodData) {
                if ($methodData['method']) {
                    $additionalData = !empty($methodData['additional']) ? $methodData['additional'] : [];
                    $additionalData['method'] = $code;
                    if ($code == 'chcybersource_cc_vault') {
                        $this->cybersourceActiveChecker->setIsReBill();
                    }
                    break;
                }
            }
            $this->errors = $this->getSubCreateModel()->setPaymentData($additionalData);
        }
    }
}
