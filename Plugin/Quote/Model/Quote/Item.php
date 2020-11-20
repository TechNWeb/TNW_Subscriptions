<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model\Quote;

/**
 * Class Item - used to change the order item representation on re-bill processing
 */
class Item
{
    /**
     * @var array
     */
    private $rebillProducts = [];

    /**
     * @param $subject
     * @param callable $proceed
     * @param $product
     * @return bool
     */
    public function aroundRepresentProduct($subject, callable $proceed, $product)
    {
        $result = $proceed($product);
        if ($result && $this->rebillProducts && isset($this->rebillProducts[$product->getId()])) {
            $result = false;
        }
        return $result;
    }

    /**
     * @param $product
     * @return $this
     */
    public function setIsReBillForProduct($product)
    {
        $this->rebillProducts[$product->getId()] = true;
        return $this;
    }
}
