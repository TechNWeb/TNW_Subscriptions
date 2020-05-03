<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment as PaymentResource;

/**
 * Subscription profile payment
 */
class Payment extends AbstractModel implements SubscriptionProfilePaymentInterface
{
    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var SubscriptionProfileInterface
     */
    private $subscriptionProfile;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param JsonHelper $jsonHelper
     * @param EncryptorInterface $encryptor
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        JsonHelper $jsonHelper,
        EncryptorInterface $encryptor,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->jsonHelper = $jsonHelper;
        $this->encryptor = $encryptor;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(PaymentResource::class);
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
    public function getPaymentToken()
    {
        return $this->encryptor->decrypt($this->getTokenHash());
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
    public function setPaymentToken($token)
    {
        return $this->setTokenHash($this->encryptor->encrypt($token));
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
     * @param string $info
     */
    public function setPaymentAdditionalInfo($info)
    {
        return $this->setData(self::PAYMENT_ADDITIONAL_INFO, $info);
    }

    /**
     * @inheritdoc
     */
    public function getDecodedPaymentAdditionalInfo()
    {
        $return = null;
        if ($this->getData(self::PAYMENT_ADDITIONAL_INFO)) {
            $return = $this->jsonHelper->jsonDecode(
                $this->getData(self::PAYMENT_ADDITIONAL_INFO)
            );
        }

        return $return;
    }

    /**
     * @inheritdoc
     * @param array $info
     */
    public function setEncodedPaymentAdditionalInfo(array $info)
    {
        return $this->setData(
            self::PAYMENT_ADDITIONAL_INFO,
            $this->jsonHelper->jsonEncode($info)
        );
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

    /**
     * @inheritdoc
     * @param bool $flag
     */
    public function setSentMail(bool $flag)
    {
        return $this->setData(self::SENT_MAIL, $flag);
    }

    /**
     * @inheritdoc
     */
    public function getSentMail()
    {
        return $this->getData(self::SENT_MAIL);
    }
}
