<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Config;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;

/**
 * Prevent disable_output if Admin area
 */
class PreventDisableOutput
{
    /**
     * @var State
     */
    private $state;

    /**
     * PreventDisableOutput constructor.
     * @param State $state
     */
    public function __construct(State $state)
    {
        $this->state = $state;
    }

    /**
     * @param ScopeConfigInterface $subject
     * @param $result
     * @param $path
     * @return mixed|string
     */
    public function afterGetValue(
        ScopeConfigInterface $subject,
        $result,
        $path
    ) {
        try {
            $areaCode = $this->state->getAreaCode();
        } catch (LocalizedException $e) {
            return $result;
        }
        if ($path === 'advanced/modules_disable_output/TNW_Subscriptions'
            && $result === '1'
            && $areaCode === Area::AREA_ADMINHTML
        ) {
            return '0';
        }
        return $result;
    }
}
