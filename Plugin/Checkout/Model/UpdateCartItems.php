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
            $subject->updateItem($itemId, $itemInfo);
        }
        return $subject;
    }
}
