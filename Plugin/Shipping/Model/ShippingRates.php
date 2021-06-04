<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Shipping\Model;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Shipping;

/**
 * Plugin, that sets rate of autoshipping method to matching from other methods
 */
class ShippingRates
{

    /**
     * @param Shipping $subject
     * @param callable $proceed
     * @param RateRequest $request
     * @return Shipping
     */
    public function aroundCollectRates(Shipping $subject, callable $proceed, RateRequest $request)
    {
        $limitCarrier = $request->getLimitCarrier();
        if ($limitCarrier === 'tnwautoship') {
            $request->unsetData('limit_carrier');
        }

        $proceed($request);

        $origResult = $subject->getResult();
        $cheapest = $origResult->getCheapestRate();
        foreach ($origResult->getAllRates() as $item) {
            if ($item->getData('carrier') === 'tnwautoship'
                && $item->getData('method') === 'cheapest'
            ) {
                $item->setPrice($cheapest->getData('price'));
                $autoShipCheapestResult = $item;
            }
        }
        if ($limitCarrier === 'tnwautoship' && isset($autoShipCheapestResult)) {
            $subject->resetResult()->getResult()->append($autoShipCheapestResult);
        }
        return $subject;
    }
}
