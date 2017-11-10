<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Quote;

use Magento\Catalog\Model\Product\Type\AbstractType;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Class PrepareBuyRequest
 */
class PrepareBuyRequest
{
    /**
     * Subscriptions product modifier.
     *
     * @var Product
     */
    private $productModifier;

    /**
     * PrepareBuyRequest constructor.
     * @param Product $productModifier
     */
    public function __construct(
        Product $productModifier
    ) {
        $this->productModifier = $productModifier;
    }

    /**
     * @param AbstractType $subject
     * @param array $result
     * @return array|string
     */
    public function afterPrepareForCartAdvanced(
        AbstractType $subject,
        $result
    ){
        if (is_array($result)){
            /** @var ProductInterface $firstItem */
            $firstItem = reset($result);
            if ($firstItem) {
                $buyRequest = $firstItem->getCustomOption('info_buyRequest');
                if ($buyRequest) {
                    $buyRequestValue = unserialize($buyRequest->getValue());
                    if (!empty($buyRequestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME])){
                        $type = $firstItem->getTypeId();
                        $this->productModifier->getBuyRequestModifier($type)
                            ->modifyBuyRequests($result);
                    }
                }
            }
        }

        return $result;
    }
}