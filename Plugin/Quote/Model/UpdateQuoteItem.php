<?php

namespace TNW\Subscriptions\Plugin\Quote\Model;

class UpdateQuoteItem
{
    /**
     * @var Quote\Item
     */
    private $quoteItemPlugin;

    /**
     * UpdateQuoteItem constructor.
     * @param Quote\Item $quoteItemPlugin
     */
    public function __construct(
        \TNW\Subscriptions\Plugin\Quote\Model\Quote\Item $quoteItemPlugin
    ) {
        $this->quoteItemPlugin = $quoteItemPlugin;
    }

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
        if ($request && $request->getData('subscribe_active') && $request->getData('rebill_processing')) {
            $this->quoteItemPlugin->setIsReBillForProduct($product);
        }
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
