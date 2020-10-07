<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Payment\Model\Checks;

/**
 * Class ZeroTotal -  modify the result for trial payment method availability
 */
class ZeroTotal
{
    /**
     * @param $subject
     * @param $proceed
     * @param $paymentMethod
     * @param \Magento\Quote\Model\Quote $quote
     * @return mixed
     */
    public function aroundIsApplicable($subject, $proceed, $paymentMethod, $quote)
    {
        $result = $proceed($paymentMethod, $quote);
        if (!$result) {
            foreach ($quote->getAllItems() as $item) {
                $option = $item->getOptionByCode('subscription');
                if (null !== $option) {
                    $result = true;
                }
            }
        }
        return $result;
    }
}
