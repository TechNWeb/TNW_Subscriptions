<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type\AbstractType;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Service\Serializer;
use Magento\Framework\Exception\LocalizedException;

/**
 * Plugin for modifying product buy request, when the product is added to the subscription.
 */
class PrepareBuyRequest
{
    /**
     * @var ProductTypeManagerResolver
     */
    private $productTypeResolver;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * PrepareBuyRequest constructor.
     * @param ProductTypeManagerResolver $productTypeResolver
     * @param Serializer $serializer
     */
    public function __construct(
        ProductTypeManagerResolver $productTypeResolver,
        Serializer $serializer
    ) {
        $this->productTypeResolver = $productTypeResolver;
        $this->serializer = $serializer;
    }

    /**
     * @param AbstractType $subject
     * @param array|mixed $result
     * @return array
     * @throws LocalizedException
     */
    public function afterPrepareForCartAdvanced(AbstractType $subject, $result)
    {
        if (!is_array($result)) {
            return $result;
        }

        /** @var ProductInterface $firstItem */
        $firstItem = reset($result);
        if ($firstItem) {
            $buyRequest = $firstItem->getCustomOption('info_buyRequest');
            if ($buyRequest) {
                $buyRequestValue = $this->serializer->unserialize($buyRequest->getValue());
                $subscriptionPart = !empty($buyRequestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME])
                    ? $buyRequestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME] : [];
                //check if we need to update request.
                // True - if in buy request exists subscription part and we need to create full request
                if (!empty($subscriptionPart[Create::FULL_REQUEST_PARAM_NAME])) {
                    $this->productTypeResolver->resolve($firstItem->getTypeId())
                        ->modifyBuyRequests($result);
                }
            }
        }

        return $result;
    }
}
