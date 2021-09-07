<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment;

/**
 * Class RequestState - used to store current payment command response
 */
class RequestState
{
    /**
     * @var
     */
    private $currentResponseObject;

    /**
     * @param $data
     * @return $this
     */
    public function setCurrentResponse($data)
    {
        $this->currentResponseObject = $data;
        return $this;
    }

    /**
     * @param bool $remove
     * @return mixed
     */
    public function getCurrentResponse($remove = true)
    {
        $result = $this->currentResponseObject;
        if ($remove) {
            $this->currentResponseObject = null;
        }
        return $result;
    }
}
