<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ProductSubscriptionProfile;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Model for bundle product management
 */
class ManagerBundle
{
    /**
     * @var PriceCurrencyInterface
     */
    private $priceFormatter;

    /**
     * ManagerBundle constructor.
     * @param PriceCurrencyInterface $priceFormatter
     */
    public function __construct(
        PriceCurrencyInterface $priceFormatter
    ) {
        $this->priceFormatter = $priceFormatter;
    }

    /**
     * Return item's bundle options data.
     *
     * @param  ProductSubscriptionProfileInterface $item
     * @return array
     */
    public function getBundleOptionsData($item)
    {
        $result = [];
        foreach ($this->getItemOptions($item) as $itemOption) {
            $currencyCode = $item[SubscriptionProfileInterface::PROFILE_CURRENCY_CODE] ?? null;
            $value = $this->getFormattedOptionValue($itemOption, $currencyCode);

            $result[] = [
                'attributeLabel' => $itemOption['label'],
                'optionLabel' => $value,
            ];
        }
        return $result;
    }

    /**
     * @param ProductSubscriptionProfileInterface $item
     * @return array
     */
    public function getItemOptions($item)
    {
        $options = $item->getCustomOptions();
        if ($options && isset($options['bundle_options'])) {
            return $options['bundle_options'];
        }
        return [];
    }

    /**
     * @param array $itemOption
     * @param string $currencyCode
     * @return string
     */
    public function getFormattedOptionValue($itemOption, $currencyCode, $withPrice = false)
    {
        $result = '';
        if (!empty($itemOption['value']) && is_array($itemOption['value'])) {
            foreach ($itemOption['value'] as $optionValue) {
                $price = $this->priceFormatter->format(
                    $optionValue['price']/$optionValue['qty'],
                    false,
                    PriceCurrencyInterface::DEFAULT_PRECISION,
                    null,
                    $currencyCode
                );
                $result .= __(
                    $withPrice ? '%1 x %2 - <b>%3</b>' : '%1 x %2',
                    $optionValue['qty'],
                    $optionValue['title'],
                    $price
                );
            }
        }
        return $result;
    }
}
