<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model;

use ArrayObject;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Api\CustomerProductHistoryManagementInterface;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Model\Product\Attribute as SubscriptionProductAttributes;
use TNW\Subscriptions\Plugin\Quote\Model\Quote\Item as ItemPlugin;
use TNW\Subscriptions\Service\Serializer;

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
     * @var CustomerProductHistoryManagementInterface
     */
    private $customerProductHistoryManagement;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * UpdateQuoteItem constructor.
     * @param ItemPlugin $quoteItemPlugin
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param CustomerProductHistoryManagementInterface $customerProductHistoryManagement
     * @param Serializer $serializer
     */
    public function __construct(
        ItemPlugin $quoteItemPlugin,
        RequestInterface $request,
        ManagerInterface $messageManager,
        CustomerProductHistoryManagementInterface $customerProductHistoryManagement,
        Serializer $serializer
    ) {
        $this->quoteItemPlugin = $quoteItemPlugin;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->customerProductHistoryManagement = $customerProductHistoryManagement;
        $this->serializer = $serializer;
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
        if (!$request->getData('rebill_processing')
            && !$request->getData('modify_profile')
            && $subject->getData('customer_id')
            && $request->getData('subscribe_active') === '1'
            && $this->isProductInTrialStatus($product, $request)
            && $request->getData('use_trial') === '1'
        ) {
            if (!$this->isProductTrialAvailableForCustomer($product, $subject->getData('customer_id'), $request)) {
                throw new LocalizedException(
                    __(
                        'Product "%1" or its child product has been ordered by you with trial option earlier. '
                        . 'If you would like to buy it again use non-trial purchase option instead.',
                        $product->getName()
                    )
                );
            }
        }
        if ($this->request->getActionName() === 'reorder'
            && $request->getData('subscribe_active') === '1'
        ) {
            if ((int)$product->getTnwSubscrPurchaseType() === PurchaseType::RECURRING_PURCHASE_TYPE) {
                $this->messageManager->addErrorMessage(
                    __(
                        'Product "%1" is Recurring-Only and cannot be reordered.'
                        . 'If you would like to re-order on recurring basis, '
                        . 'please subscribe to it instead.',
                        $product->getName()
                    )
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
        if ($this->request->getActionName() === 'reorder'
            && $request->getData('subscribe_active') === '1'
            && (int)$product->getTnwSubscrPurchaseType() === PurchaseType::RECURRING_PURCHASE_TYPE
        ) {
            $subject->deleteItem($result);
        }
        return $result;
    }

    /**
     * @param Quote $subject
     * @param $result
     * @return mixed
     */
    public function afterMerge(
        Quote $subject,
        Quote $result
    ) {
        $addProductList = new ArrayObject();
        foreach ($result->getAllItems() as $item) {
            if ($item->getChildren()) {
                continue;
            }
            $buyRequest = $item->getBuyRequest();
            if (($subscriptionData = $buyRequest->getData('subscription_data'))
                && isset($subscriptionData['unique']['is_trial'])
                && $subscriptionData['unique']['is_trial'] === true
            ) {
                if (!$this->customerProductHistoryManagement->isProductTrialAvailableForCustomer(
                    $result->getData('customer_id'),
                    $item->getProduct()->getId()
                )) {
                    $itemToDelete = $item;
                    if ($item->getParentItemId()) {
                        $itemToDelete = $item->getParentItem();
                        $buyRequest = $itemToDelete->getBuyRequest();
                    }
                    $buyRequest->setData('modify_profile', true);
                    foreach (['custom_price', 'subscription_data', 'use_preset_qty', 'hide_qty'] as $key) {
                        $buyRequest->unsetData($key);
                    }

                    $result->deleteItem($itemToDelete);
                    $addProductList->append([
                        'item' => clone $itemToDelete,
                        'itemProduct' => clone $itemToDelete->getProduct(),
                        'buyRequest' => clone $buyRequest,
                    ]);
                }
            }
        }
        foreach ($addProductList->getIterator() as $itemToAdd) {
            try {
                $result->addProduct($itemToAdd['itemProduct'], $itemToAdd['buyRequest']);
            } catch (LocalizedException $e) {
                $result->deleteItem($itemToAdd['item']);
            }
        }
        return $result;
    }

    /**
     * @param Product $product
     * @param $request
     * @return bool
     */
    private function isProductInTrialStatus(
        Product $product,
        $request
    ) {
        $productTrialStatus = $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_STATUS);
        if ($request && $request->getData('subs_group')) {
            $subsGroup = $request->getData('subs_group');
            foreach ($subsGroup as $id => $group) {
                $childProductTrialStatus = $group['use_trial'] ?? null;
                if ($childProductTrialStatus) {
                    $productTrialStatus = $childProductTrialStatus;
                    break;
                }
            }
        }
        if ($request && !empty($product->getData(SubscriptionProductAttributes::SUBSCRIPTION_INHERITANCE))) {
            $subscriptionInheritance = $this->serializer->unserialize(
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_INHERITANCE)
            );
            if (isset($subscriptionInheritance[SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_STATUS])
                && $subscriptionInheritance[SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_STATUS] == 1
            ) {
                $selectedConfigurableOption = $request->getData('selected_configurable_option');
                if (!empty($selectedConfigurableOption)) {
                    $usedProducts = $product->getTypeInstance()->getUsedProducts($product);
                    foreach ($usedProducts as $variation) {
                        if ($variation->getId() == $selectedConfigurableOption) {
                            $productTrialStatus = $variation->getData(
                                SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_STATUS
                            );
                            break;
                        }
                    }
                }
            }
        }
        return (bool)$productTrialStatus;
    }

    /**
     * @param int $customer_id
     * @param Product $product
     * @param $request
     * @return bool
     */
    private function isProductTrialAvailableForCustomer(
        Product $product,
        int $customer_id,
        $request
    ) {
        $productIds = [
            $product->getId()
        ];

        $customerProductHistoryList =
            $this->customerProductHistoryManagement->getUniqueProductsInSubscriptionsForCustomer($customer_id);

        if ($request && $request->getData('subs_group')) {
            $productIds = [];
            $subsGroup = $request->getData('subs_group') ?? [];
            foreach ($subsGroup as $id => $group) {
                $childProductTrialStatus = $group['use_trial'] ?? null;
                if ($childProductTrialStatus) {
                    $productIds[] = $id;
                }
            }
        }
        if ($request && $product->getTypeId() === Configurable::TYPE_CODE) {
            $productIds = [];
            if (!empty($request->getData('selected_configurable_option'))) {
                $productIds[] = $request->getData('selected_configurable_option');
            }
        }
        foreach ($productIds as $id) {
            if (in_array((int)$id, $customerProductHistoryList, true)) {
                return false;
            }
        }
        return true;
    }
}
