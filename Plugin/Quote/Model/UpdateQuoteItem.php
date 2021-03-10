<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Plugin\Quote\Model\Quote\Item as ItemPlugin;

/**
 * Class UpdateQuoteItem - plugin to change the data for \Magento\Quote\Model\Quote::addProduct method
 */
class UpdateQuoteItem
{
    /**
     * @var ItemPlugin
     */
    private $quoteItemPlugin;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * UpdateQuoteItem constructor.
     * @param ItemPlugin $quoteItemPlugin
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        ItemPlugin $quoteItemPlugin,
        RequestInterface $request,
        ManagerInterface $messageManager
    ) {
        $this->quoteItemPlugin = $quoteItemPlugin;
        $this->request = $request;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Quote $subject
     * @param Product $product
     * @param $request
     * @param $processMode
     * @return array
     * @throws LocalizedException
     */
    public function beforeAddProduct(
        Quote $subject,
        Product $product,
        $request = null,
        $processMode = AbstractType::PROCESS_MODE_FULL
    ) {
        if ($request && $request->getData('subscribe_active') && $request->getData('rebill_processing')) {
            $this->quoteItemPlugin->setIsReBillForProduct($product);
        }
        if (
            $this->request->getActionName() === 'reorder'
            && $request->getData('subscribe_active') === '1'
        ) {
            if ((int)$product->getTnwSubscrPurchaseType() === PurchaseType::RECURRING_PURCHASE_TYPE) {
                $this->messageManager->addErrorMessage(
                    __('Product "%1" is Recurring-Only and cannot be reordered.'
                        . 'If you would like to re-order on recurring basis, '
                        . 'please subscribe to it instead.'
                        , $product->getName())
                );
            } else {
                $request->setData('subscribe_active', 0);
                $request->unsetData('custom_price');
                $this->messageManager->addNoticeMessage(
                    __('Reorder allows you to order products again on a one-off basis. '
                        . 'If you would like to re-order the same product on recurring basis, '
                        . 'please subscribe to those products instead.')
                );
            }
        }
        if ($request->getAddtocartType() !== null) {
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

    /**
     * @param Quote $subject
     * @param $result
     * @param Product $product
     * @param null $request
     */
    public function afterAddProduct(
        Quote $subject,
        $result,
        Product $product,
        $request = null
    ) {
        if (
            $this->request->getActionName() === 'reorder'
            && $request->getData('subscribe_active') === '1'
            && (int)$product->getTnwSubscrPurchaseType() === PurchaseType::RECURRING_PURCHASE_TYPE
        ) {
            $subject->deleteItem($result);
        }
        return $result;
    }
}
