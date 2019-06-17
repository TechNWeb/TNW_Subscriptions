<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Catalog\Model\Product\Type;

class AbstractType
{
    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product
     */
    private $productModifier;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $localeResolver;

    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product $productModifier,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        $this->productModifier = $productModifier;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @param \Magento\Catalog\Model\Product\Type\AbstractType $subject
     * @param callable $callback
     * @param \Magento\Framework\DataObject $buyRequest
     * @param \Magento\Catalog\Model\Product $product
     * @param null $processMode
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
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
            if (isset($buyRequest['subscribe_qty'])) {
                $buyRequest['qty'] = \Zend_Filter::filterStatic(
                    $buyRequest['subscribe_qty'],
                    'LocalizedToNormalized',
                    [['locale' => $this->localeResolver->getLocale()]]
                );

                unset($buyRequest['subscribe_qty']);
            }

            //TODO: refactor
            $this->productModifier->reset();
            $this->productModifier->setData($buyRequest->getData());
            $this->productModifier->setProduct($product);

            $preparedBuyRequest = $this->productModifier->getPreparedBuyRequest(true);
            $buyRequest->setData($preparedBuyRequest->getData());

            $product->addCustomOption(
                'subscription',
                \json_encode($preparedBuyRequest->getDataByPath('subscription_data/unique'))
            );
        }

        return $callback($buyRequest, $product, $processMode);
    }
}
