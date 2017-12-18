<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Quote;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type\AbstractType;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\Manager;
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
     * @var Manager
     */
    private $productManager;

    /**
     * @param Manager $productModifier
     */
    public function __construct(
        Manager $productManager
    ) {
        $this->productManager = $productManager;
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
                    if (!empty($buyRequestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME])) {
                        $type = $firstItem->getTypeId();
                        $this->productManager->getProductManagerByType($type)
                            ->modifyBuyRequests($result);
                    }
                }
            }
        }

        return $result;
    }
}
