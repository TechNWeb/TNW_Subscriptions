<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model\Quote\Item;

class Processor
{
    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product
     */
    private $productModifier;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $localeResolver;

    public function __construct(
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product $productModifier,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->productModifier = $productModifier;
        $this->localeResolver = $localeResolver;
    }

    public function aroundPrepare(
        \Magento\Quote\Model\Quote\Item\Processor $subject,
        callable $callback,
        \Magento\Quote\Model\Quote\Item $item,
        \Magento\Framework\DataObject $request,
        \Magento\Catalog\Model\Product $candidate
    ) {
        $callback($item, $request, $candidate);

        $buyRequest = $item->getBuyRequest();
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
            $this->productModifier->setProduct($candidate);

            $preparedBuyRequest = $this->productModifier->getPreparedBuyRequest();

            // Set qty
            $item->setQty($preparedBuyRequest->getData('qty'));

            // Set custom price
            $customPrice = $preparedBuyRequest->getData('custom_price');

            $item->setCustomPrice($customPrice);
            $item->setOriginalCustomPrice($customPrice);

            // Set subscription option
            $option = $this->dataObjectFactory->create()->setData([
                'product_id' => $candidate->getId(),
                'product' => $candidate,
                'code' => 'subscription',
                'value' => \json_encode($preparedBuyRequest->getDataByPath('subscription_data/unique'))
            ]);

            $item->addOption($option);

            // Set initial fee
            $this->productModifier->setInitialFeeToItem($item);
        }
    }
}
