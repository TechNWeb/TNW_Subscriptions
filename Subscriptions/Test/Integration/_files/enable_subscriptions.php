<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\ReinitableConfig;
use Magento\TestFramework\Helper\Bootstrap;

/** @var Value $generalActive */
$generalActive = Bootstrap::getObjectManager()->create(Value::class);
$generalActive->setPath('tnw_subscriptions_general/general/active');
$generalActive->setValue(1);
$generalActive->save();

/** @var Value $enableCheckmo */
$enableCheckmo = Bootstrap::getObjectManager()->create(Value::class);
$enableCheckmo->setPath('tnw_subscriptions_payment_methods/active_methods/checkmo');
$enableCheckmo->setValue(1);
$enableCheckmo->save();

/** @var ReinitableConfig $reinitableConfig */
$reinitableConfig = Bootstrap::getObjectManager()->get(ReinitableConfig::class);
$reinitableConfig->reinit();
