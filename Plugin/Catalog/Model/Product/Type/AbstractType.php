<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Catalog\Model\Product\Type;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use TNW\Subscriptions\Model\Config\Product\SubscriptionProductView;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product as SubscriptionProduct;

/**
 * Class AbstractType - plugin to modify data for all products on adding to cart
 */
class AbstractType
{
    /**
     * @var SubscriptionProduct
     */
    private $productModifier;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var SubscriptionProductView
     */
    private $subscriptionProductView;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * AbstractType constructor.
     * @param SubscriptionProduct $productModifier
     * @param ResolverInterface $localeResolver
     * @param SubscriptionProductView $subscriptionProductView
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        SubscriptionProduct $productModifier,
        ResolverInterface $localeResolver,
        SubscriptionProductView $subscriptionProductView,
        ResultFactory $resultFactory
    ) {
        $this->productModifier = $productModifier;
        $this->localeResolver = $localeResolver;
        $this->subscriptionProductView = $subscriptionProductView;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @param Product\Type\AbstractType $subject
     * @param callable $callback
     * @param DataObject $buyRequest
     * @param Product $product
     * @param null $processMode
     *
     * @return mixed
     * @throws LocalizedException
     * @throws \Zend_Filter_Exception
     */
    public function aroundPrepareForCartAdvanced(
        Product\Type\AbstractType $subject,
        callable $callback,
        DataObject $buyRequest,
        $product,
        $processMode = null
    ) {
        /** Checking is customer group allowed */
        if (!$this->subscriptionProductView->getCustomerGroupLimitation($product)
            && $this->subscriptionProductView->isOnlySubscribePurchase($product)
        ) {
            return __('Product is not available for purchase at this time')->render();
        }
        if (isset($buyRequest['subscribe_active'])
            && $buyRequest['subscribe_active']
            && !($buyRequest->getBundleOption() && $product->getTypeId() !== Type::TYPE_CODE)
        ) {
            if (isset($buyRequest['subscribe_qty'])) {
                $buyRequest['qty'] = \Zend_Filter::filterStatic(
                    (string)$buyRequest['subscribe_qty'],
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
