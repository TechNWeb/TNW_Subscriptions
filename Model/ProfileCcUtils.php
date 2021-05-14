<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\EnginePool;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 *  Credit card utility methods.
 */
class ProfileCcUtils
{
    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var EnginePool
     */
    private $enginePool;

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;

    /**
     * @param JsonHelper $jsonHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(JsonHelper $jsonHelper, EnginePool $enginePool, ScopeConfigInterface $scopeConfig)
    {
        $this->jsonHelper = $jsonHelper;
        $this->enginePool = $enginePool;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Object use credit card payment method or not.
     *
     * @param \Magento\Framework\DataObject $object
     * @return bool
     */
    public function isCcPayment($object)
    {
        return in_array($object->getEngineCode(), $this->enginePool->getCcEngineList(), true);
    }

    /**
     * Returns credit card expiration datetime.
     *
     * @param array $paymentInfo
     * @return \DateTime|null
     */
    public function getCcExpireDate(array $paymentInfo)
    {
        $expiredAt = null;

        if (isset($paymentInfo[OrderPaymentInterface::CC_EXP_MONTH]) &&
            isset($paymentInfo[OrderPaymentInterface::CC_EXP_YEAR])
        ) {
            $lastDayOfMonth = (int)date("t");
            $expiredAt = new \DateTime(
                sprintf(
                    "%s-%s-%s",
                    $paymentInfo[OrderPaymentInterface::CC_EXP_YEAR],
                    (int) $paymentInfo[OrderPaymentInterface::CC_EXP_MONTH],
                    $lastDayOfMonth
                )
            );
        }

        return $expiredAt;
    }

    /**
     * Will Cc expire before payment date or not.
     *
     * @param SubscriptionProfileInterface|\Magento\Framework\DataObject $object
     * @param \DateTime|string $paymentDate
     * @param $notificationPeriod
     * @return bool
     */
    public function isCcExpireBy($object, $paymentDate, bool $notificationPeriod = false)
    {
        if ($object->getPayment() && $this->isCcPayment($object->getPayment())) {
            $paymentInfo = $object->getPayment()->getPaymentAdditionalInfo();
            if (!empty($paymentInfo) && is_string($paymentInfo)) {
                $paymentInfo = $this->jsonHelper->jsonDecode($paymentInfo);
                $expiredAt = $this->getCcExpireDate($paymentInfo);
                if ($notificationPeriod != false) {
                    $dayModifier = '+'
                        . $this->scopeConfig->getValue(EmailNotifier::XML_PATH_EXPIRED_CARD_NOTIFICATION_PERIOD)
                        . ' day';
                    $currentDate = new \DateTime('now');
                    $currentDate->modify($dayModifier);
                    return $expiredAt < $currentDate;
                }
                if (null !== $expiredAt && !empty($paymentDate)) {
                    $paymentDate = ($paymentDate instanceof \DateTime)
                        ? $paymentDate
                        : new \DateTime($paymentDate);

                    return $expiredAt < $paymentDate;
                }
            }
        }

        return false;
    }
}
