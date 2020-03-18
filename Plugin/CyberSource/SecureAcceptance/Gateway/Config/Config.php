<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\CyberSource\SecureAcceptance\Gateway\Config;

/**
 * Class Config
 * @package TNW\Subscriptions\Plugin\CyberSource\SecureAcceptance\Gateway\Config
 */
class Config
{
    /**
     * @var bool
     */
    private $isReBill = false;

    /**
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterGetIsLegacyMode(
        $subject,
        $result
    ) {
        if ($result && $this->isReBill) {
            $result = $subject->getValue(\CyberSource\SecureAcceptance\Gateway\Config\Config::KEY_MODE);
        }
        return $result;
    }

    /**
     * Set SubscriptionProcessing Flag
     */
    public function reBillProcess()
    {
        $this->isReBill = true;
    }
}
