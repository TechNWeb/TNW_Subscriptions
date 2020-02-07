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
     * @param JsonHelper $jsonHelper
     */
    public function __construct(JsonHelper $jsonHelper, EnginePool $enginePool)
    {
        $this->jsonHelper = $jsonHelper;
        $this->enginePool = $enginePool;
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

        if (
            isset($paymentInfo[OrderPaymentInterface::CC_EXP_MONTH]) &&
            isset($paymentInfo[OrderPaymentInterface::CC_EXP_YEAR])
        ) {
            $expiredAt = new \DateTime(
                sprintf(
                    "%s-%s-01",
                    $paymentInfo[OrderPaymentInterface::CC_EXP_YEAR],
                    $paymentInfo[OrderPaymentInterface::CC_EXP_MONTH]
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
     * @return bool
     */
    public function isCcExpireBy($object, $paymentDate)
    {
        if ($this->isCcPayment($object->getPayment())) {
            $paymentInfo = $object->getPayment()->getPaymentAdditionalInfo();
            if (!empty($paymentInfo) && is_string($paymentInfo)) {
                $paymentInfo = $this->jsonHelper->jsonDecode($paymentInfo);
                $expiredAt = $this->getCcExpireDate($paymentInfo);
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
