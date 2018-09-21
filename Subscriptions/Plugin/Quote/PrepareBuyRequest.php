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
use TNW\Subscriptions\Model\SubscriptionProfile\Quote\Item\OptionValueResolver;

/**
 * Plugin for modifying product buy request, when the product is added to the subscription.
 */
class PrepareBuyRequest
{
    /**
     * Subscriptions product manager.
     *
     * @var ProductTypeManagerResolver
     */
    private $productTypeResolver;

    /**
     * @param ProductTypeManagerResolver $productTypeResolver
     */
    public function __construct(ProductTypeManagerResolver $productTypeResolver)
    {
        $this->productTypeResolver = $productTypeResolver;
    }

    /**
     * After preparing product modifies product buy request.
     *
     * @param AbstractType $subject
     * @param array $result
     * @return array|string
     */
    public function afterPrepareForCartAdvanced(AbstractType $subject, $result)
    {
        if (is_array($result)) {
            /** @var ProductInterface $firstItem */
            $firstItem = reset($result);
            if ($firstItem) {
                $buyRequest = $firstItem->getCustomOption('info_buyRequest');
                if ($buyRequest) {
                    $buyRequestValue = OptionValueResolver::getDecodedValue($buyRequest->getValue());
                    $subscriptionPart = !empty($buyRequestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME])
                        ? $buyRequestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME] : [];
                    //check if we need to update request.
                    // True - if in buy request exists subscription part and we need to create full request
                    if (!empty($subscriptionPart[Create::FULL_REQUEST_PARAM_NAME])) {
                        $type = $firstItem->getTypeId();
                        $this->productTypeResolver->resolve($type)
                            ->modifyBuyRequests($result);
                    }
                }
            }
        }

        return $result;
    }
}
