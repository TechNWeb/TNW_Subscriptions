<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Catalog\Model\Product;

use Magento\Framework\Locale\ResolverInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product;

class AbstractType
{
    /**
     * @var Product
     */
    private $productModifier;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    public function __construct(
        Product $productModifier,
        ResolverInterface $localeResolver
    ) {
        $this->productModifier = $productModifier;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param callable $callback
     * @param \Magento\Framework\DataObject $buyRequest
     * @param \Magento\Catalog\Model\Product $product
     * @param null|string $processMode
     *
     * @return array|string
     * @throws \Zend_Filter_Exception
     */
    public function aroundPrepareForCartAdvanced(
        \Magento\Catalog\Model\Product\Type\AbstractType $subject,
        callable $callback,
        \Magento\Framework\DataObject $buyRequest,
        $product,
        $processMode = null
    ) {
        if (isset($buyRequest['subscribe_active']) && $buyRequest['subscribe_active']) {
            try {
                if (isset($buyRequest['subscribe_qty'])) {
                    $buyRequest['qty'] = \Zend_Filter::filterStatic(
                        $buyRequest['subscribe_qty'],
                        'LocalizedToNormalized',
                        [['locale' => $this->localeResolver->getLocale()]]
                    );

                    unset($buyRequest['subscribe_qty']);
                }

                //TODO: Refactoring
                $this->productModifier->reset();
                $this->productModifier->setData($buyRequest->getData());
                $this->productModifier->setProduct($product);

                $buyRequest->setData($this->productModifier->getPreparedBuyRequest()->getData());
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                return $e->getMessage();
            }
        }

        return $callback($buyRequest, $product, $processMode);
    }
}
