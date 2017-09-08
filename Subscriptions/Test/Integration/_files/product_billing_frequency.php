<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

use Magento\TestFramework\Helper\Bootstrap;
use TNW\Subscriptions\Model\ProductBillingFrequency;

include __DIR__ . '/billing_frequency.php';

/** @var ProductBillingFrequency $productBillingFrequency */
$productBillingFrequency = Bootstrap::getObjectManager()->create(ProductBillingFrequency::class);
$productBillingFrequency->setPrice(10);
$productBillingFrequency->setBillingFrequencyId($billingFrequency->getId());
$productBillingFrequency->setInitialFee(0);
