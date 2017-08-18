<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Model\SubscriptionProfile\Engine\CheckmoFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\EngineInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\InvalidEngineException;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\PayflowproFactory;

/**
 * Class EnginePool
 */
class EnginePool
{
    const ENGINE_CODE_CHECKMO = 'checkmo';
    const ENGINE_CODE_PAYFLOW = 'payflowpro';

    const ERROR_INVALID_ENGINE = "Invalid engine code: '%1'";

    /**
     * Factory for creating checkmo engine.
     *
     * @var CheckmoFactory
     */

    private $checkmoFactory;

    /**
     * Factory for creating payflowpro engine.
     *
     * @var PayflowproFactory
     */
    private $payflowproFactory;

    /**
     * EnginePool constructor.
     * @param CheckmoFactory $checkmoFactory
     * @param PayflowproFactory $payflowproFactory
     */
    public function __construct(
        CheckmoFactory $checkmoFactory,
        PayflowproFactory $payflowproFactory
    ) {
        $this->checkmoFactory = $checkmoFactory;
        $this->payflowproFactory = $payflowproFactory;
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
            case self::ENGINE_CODE_PAYFLOW:
                $result = $this->payflowproFactory->create();
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
            self::ENGINE_CODE_CHECKMO,
            self::ENGINE_CODE_PAYFLOW
        ];
    }
}