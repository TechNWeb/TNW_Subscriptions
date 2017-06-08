<?php

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\EngineInterface;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

class Manager
{
    /** @var SubscriptionProfile */
    protected $profile;
    /** @var EngineInterface */
    protected $engine;
    /** @var EnginePool */
    protected $enginePool;
    /** @var SubscriptionProfileRepository */
    protected $subscriptionProfileRepository;

    public function __construct(
        EnginePool $enginePool,
        SubscriptionProfileRepository $subscriptionProfileRepository,
        SubscriptionProfileFactory $subscriptionProfileFactory
    ) {
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->subscriptionProfileFactory = $subscriptionProfileFactory;
        $this->enginePool = $enginePool;
    }

    /**
     * @param SubscriptionProfile $profile
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;
    }

    public function reset()
    {
        $this->profile = null;
        $this->engine = null;
    }

    /**
     * @return EngineInterface
     */
    public function getEngine()
    {
        if (!$this->engine){
            $engineCode = $this->profile->getEngineCode();

            /** @var EngineInterface $engine */
            $this->engine = $this->enginePool->getEngineByCode($engineCode);
        }

        return $this->engine;
    }

    public function saveProfile()
    {
        $this->getEngine()->updateProfile(
            $this->profile
        );

        $this->subscriptionProfileRepository->save($this->profile);
    }

    public function processProfile()
    {
        $this->getEngine()->processProfile(
            $this->profile
        );
    }

    public function updateShippingAddress()
    {

    }

    public function updateBillingAddress()
    {

    }

    public function updatePayment()
    {

    }

    public function updateShipping()
    {

    }

    /**
     * @return SubscriptionProfile
     */
    public function getEmptyProfile()
    {
        return $this->subscriptionProfileFactory->create();
    }
}