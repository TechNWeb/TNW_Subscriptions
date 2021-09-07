<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\ReBill as ResourceModel;
use Magento\Framework\Serialize\Serializer\Json;
use TNW\Subscriptions\Api\Data\ReBillInterface;

/**
 * Class ReBill - used as data object for susbcription re-bill entities
 */
class ReBill extends AbstractModel implements ReBillInterface
{
    /**
     * @var Json
     */
    protected $serializer;

    /**
     * ReBill constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Json $serializer
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Json $serializer,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->serializer = $serializer;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @param $profileIds
     * @return ReBill
     */
    public function setSubscriptionProfiles($profileIds)
    {
        return $this->setData('subscription_profiles', $this->serializer->serialize($profileIds));
    }

    /**
     * @return array|bool|float|int|mixed|string|null
     */
    public function getSubscriptionProfiles()
    {
        return $this->serializer->unserialize($this->getData('subscription_profiles'));
    }

    /**
     * @param $queueIds
     * @return ReBill
     */
    public function setQueues($queueIds)
    {
        return $this->setData('queues', $this->serializer->serialize($queueIds));
    }

    /**
     * @return array|bool|float|int|mixed|string|null
     */
    public function getQueues()
    {
        return $this->serializer->unserialize($this->getData('queues'));
    }
}
