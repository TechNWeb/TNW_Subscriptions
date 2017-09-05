<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

use Magento\TestFramework\Helper\Bootstrap;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Model\BillingFrequency;

/** @var BillingFrequency $billingFrequency */
$billingFrequency = Bootstrap::getObjectManager()->create(BillingFrequency::class);
/** @var BillingFrequencyRepositoryInterface $billingFrequencyRepository */
$billingFrequencyRepository = Bootstrap::getObjectManager()->create(BillingFrequencyRepositoryInterface::class);

$billingFrequency->addData([
    'label' => 'test billing frequency',
    'status' => '1',
    'frequency' => 5,
    'website_id' => 1,
    'unit' => 3,
]);

$billingFrequencyRepository->save($billingFrequency);
