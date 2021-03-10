<?php

namespace TNW\Subscriptions\Plugin\Checkout\Model;

class UpdateCartItems
{
    /**
     * @param \Magento\Checkout\Model\Cart $subject
     * @param callable $proceed
     * @param array $data
     * @return \Magento\Checkout\Model\Cart
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundUpdateItems(\Magento\Checkout\Model\Cart $subject, callable $proceed, $data)
    {
        $proceed($data);
        foreach ($data as $itemId => $itemInfo) {
            if (is_array($itemInfo) && isset($itemInfo['subscribe_active'])) {
                /**
                 * If update is triggered by 'Update Shopping Cart' button in cart
                 * we need to update not only qty, but also subscription options,
                 * only if they present in request
                 */
                $subject->updateItem($itemId, $itemInfo);
            }
        }
        return $subject;
    }
}
