<?php

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Model\SubscriptionProfile\Engine\CheckmoFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\EngineInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\InvalidEngineException;

class EnginePool
{
    const ENGINE_CODE_CHECKMO = 'checkmo';

    const ERROR_INVALID_ENGINE = "Invalid engine code: '%1'";

    /** @var CheckmoFactory */
    protected $checkmoFactory;


    public function __construct(
        CheckmoFactory $checkmoFactory
    ) {
        $this->checkmoFactory = $checkmoFactory;
    }

    /**
     * @param $engine
     * @return EngineInterface
     * @throws InvalidEngineException
     */
    public function getEngineByCode($engine)
    {
        $result = null;
        switch ($engine) {
            case self::ENGINE_CODE_CHECKMO:
                $result = $this->checkmoFactory->create();
                break;
            default:
                throw new InvalidEngineException(__(self::ERROR_INVALID_ENGINE,
                    $engine));
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getEngineList()
    {
        return [
            self::ENGINE_CODE_CHECKMO
        ];
    }
}