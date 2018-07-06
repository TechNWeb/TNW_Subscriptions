<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Model\SubscriptionProfile\Engine\EngineInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\InvalidEngineException;

/**
 * Class EnginePool
 */
class EnginePool
{
    /**
     * @var array
     */
    private $engine;

    /**
     * EnginePool constructor.
     * @param array $engine
     */
    public function __construct(
        array $engine
    ) {
        $this->engine = $engine;
    }

    /**
     * Returns engine instance by code.
     *
     * @param string $code
     * @return EngineInterface
     * @throws InvalidEngineException
     */
    public function getEngineByCode($code)
    {
        foreach ($this->engine as $engine) {
            if (strcasecmp($engine['code'], $code) !== 0) {
                continue;
            }

            return $engine['factory']->create();
        }

        throw new InvalidEngineException(__("Invalid engine code: '%1'", $code));
    }

    /**
     * Returns allowed engine codes.
     *
     * @return array
     */
    public function getEngineList()
    {
        return array_map([$this, 'mapperCode'], $this->engine);
    }

    /**
     * Returns engine codes working with credit cards.
     *
     * @return array
     */
    public function getCcEngineList()
    {
        return array_map([$this, 'mapperCode'], array_filter($this->engine, [$this, 'filterCc']));
    }

    /**
     * Mapper
     * @param array $engine
     * @return string
     */
    private function mapperCode(array $engine)
    {
        return $engine['code'];
    }

    /**
     * Filter
     * @param array $engine
     * @return bool
     */
    private function filterCc(array $engine)
    {
        return $engine['cc'];
    }
}
