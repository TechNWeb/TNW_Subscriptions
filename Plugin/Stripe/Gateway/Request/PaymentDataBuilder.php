<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Stripe\Gateway\Request;

use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class PaymentDataBuilder - used to set 3ds for re-bills
 */
class PaymentDataBuilder
{
    /**
     * @var mixed|null
     */
    private $subjectReader = null;

    /**
     * PaymentDataBuilder constructor.
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            $this->subjectReader = $objectManager
                ->get(\TNW\Stripe\Gateway\Helper\SubjectReader::class);

        }
    }

    /**
     * @param $stripeDataBuilder
     * @param $result
     * @param array $subject
     * @return mixed
     */
    public function afterBuild($stripeDataBuilder, $result, array $subject)
    {
        if ($this->subjectReader) {
            $paymentDO = $this->subjectReader->readPayment($subject);
            $payment = $paymentDO->getPayment();
            if ($payment && $payment->getAdditionalInformation()) {
                $additionalPaymentInformation = $payment->getAdditionalInformation();
                if ((isset($additionalPaymentInformation['is_rebill']) && $additionalPaymentInformation['is_rebill'])
                || (
                    isset($additionalPaymentInformation['is_admin_subscription_creation'])
                    && $additionalPaymentInformation['is_admin_subscription_creation']
                    )
                ) {
                    $result['off_session'] = true;
                    $result['confirm'] = true;
                    if (isset($additionalPaymentInformation['set_pm']) && $additionalPaymentInformation['set_pm']) {
                        $result['set_pm'] = true;
                    }
                }
            }
        }
        return $result;
    }
}
