<?php

namespace TNW\Subscriptions\Plugin\Quote\Model;

class UpdateQuoteItem
{
    /**
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Catalog\Model\Product $product
     * @param $request
     * @param $processMode
     * @return array
     */
    public function beforeAddProduct(
        \Magento\Quote\Model\Quote $subject,
        \Magento\Catalog\Model\Product $product,
        $request = null,
        $processMode = \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL
    ) {
        if ($request->getAddtocartType()) {
            return [$product, $request, $processMode];
        }
        $modifiedRequest = $request;
        $currentConfig = $request->getDataByPath('_processing_params/current_config');
        if ($currentConfig && $currentConfig->getSubscriptionData()) {
            $modifiedRequest->setSubscribeActive($currentConfig->getSubscribeActive())
                ->setBillingFrequency($currentConfig->getBillingFrequency())
                ->setTerm($currentConfig->getTerm())
                ->setPeriod($currentConfig->getPeriod())
                ->setStartOn($currentConfig->getStartOn())
                ->setUsePresetQty($currentConfig->getUsePresetQty())
                ->setCustomPrice($currentConfig->getCustomPrice())
                ->setSubscriptionData($currentConfig->getSubscriptionData());
        }
        return [$product, $modifiedRequest, $processMode];
    }
}
