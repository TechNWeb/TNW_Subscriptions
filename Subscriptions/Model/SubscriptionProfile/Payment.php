<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;

class Payment extends \Magento\Framework\Model\AbstractModel implements SubscriptionProfilePaymentInterface
{
    /**
     * @var SubscriptionProfileInterface
     */
    private $subscriptionProfile;

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment::class);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getData(self::PAYMENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        return $this->setData(self::PAYMENT_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function getProfileId()
    {
        return $this->getData(self::PROFILE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setProfileId($profileId)
    {
        return $this->setData(self::PROFILE_ID, $profileId);
    }

    /**
     * @inheritdoc
     */
    public function getEngineCode()
    {
        return $this->getData(self::ENGINE_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setEngineCode($engineCode)
    {
        return $this->setData(self::ENGINE_CODE, $engineCode);
    }

    /**
     * @inheritdoc
     */
    public function getTokenHash()
    {
        return $this->getData(self::TOKEN_HASH);
    }

    /**
     * @inheritdoc
     */
    public function setTokenHash($tokenHash)
    {
        return $this->setData(self::TOKEN_HASH, $tokenHash);
    }

    /**
     * @inheritdoc
     */
    public function getPaymentAdditionalInfo()
    {
        return $this->getData(self::PAYMENT_ADDITIONAL_INFO);
    }

    /**
     * @inheritdoc
     */
    public function setPaymentAdditionalInfo($info)
    {
        return $this->setData(self::PAYMENT_ADDITIONAL_INFO, $info);
    }

    /**
     * @inheritdoc
     */
    public function setSubscriptionProfile(SubscriptionProfileInterface $subscriptionProfile)
    {
        $this->subscriptionProfile = $subscriptionProfile;
        $this->setProfileId($subscriptionProfile->getId());
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionProfile()
    {
        return $this->subscriptionProfile;
    }

}
