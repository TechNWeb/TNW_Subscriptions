<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin;

use Magento\Framework\App\State;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Output\ConfigInterface;

/**
 * Class IgnoreConfigurationForAdmin for modify isEnabled module
 */
class IgnoreConfigurationForAdmin
{
    /**
     * @var State
     */
    private $state;

    /**
     * IgnoreConfigurationForAdmin constructor.
     * @param State $state
     */
    public function __construct(
        State $state
    ) {
        $this->state = $state;
    }

    /**
     * Set show module output for admin area even if config is disable
     *
     * @param  ConfigInterface $config
     * @param  $result
     * @param  $moduleName
     * @return false|mixed
     * @throws LocalizedException
     */
    public function afterIsEnabled(ConfigInterface $config, $result, $moduleName)
    {
        try {
            $state = $this->state->getAreaCode();
            if ($moduleName == 'TNW_Subscriptions' && $state == FrontNameResolver::AREA_CODE) {
                $result = false;
            }
        } catch (LocalizedException $e) {
        }
        return $result;
    }
}
