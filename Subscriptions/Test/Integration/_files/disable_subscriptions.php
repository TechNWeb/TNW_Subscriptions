<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\ReinitableConfig;
use Magento\TestFramework\Helper\Bootstrap;

/** @var Value $generalActive */
$generalActive = Bootstrap::getObjectManager()->create(Value::class);
$generalActive->setPath('tnw_subscriptions_general/general/active');
$generalActive->setValue(0);
$generalActive->save();

/** @var ReinitableConfig $reinitableConfig */
$reinitableConfig = Bootstrap::getObjectManager()->get(ReinitableConfig::class);
$reinitableConfig->reinit();
