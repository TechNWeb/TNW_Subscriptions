<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Pricing\Render;

use Magento\Framework\Pricing\Render\Amount as BaseAmount;

/**
 * Subscription price amount renderer
 */
class SubscriptionAmount extends BaseAmount
{
    /**
     * Retrieve top|bottom message to subscription price.
     *
     * @param string $type
     * @return string
     */
    public function getMessage(string $type)
    {
        $message = '';
        if ($type) {
            $defaultData = $this->getPriceData();
            $message = isset($defaultData[$type . '_message']) ? $defaultData[$type . '_message'] : '';
        }
        return $message;
    }

    /**
     * Retrieve price.
     *
     * @return string
     */
    public function getPrice()
    {
        $defaultData = $this->getPriceData();
        return isset($defaultData['price']) ? $defaultData['price'] : '';
    }

    /**
     * Retrieve format price.
     *
     * @return string
     */
    public function getDisplayPrice()
    {
        $defaultData = $this->getPriceData();
        if ($defaultData['trial_price_status'] && empty($defaultData['price'])) {
            return sprintf('<span class="free">%s</span>', __('Free'));
        }

        $result = $this->formatCurrency($defaultData['price']);
        if ($defaultData['frequency_unit_message']) {
            $result .= $defaultData['frequency_unit_message'];
        }

        return $result;
    }

    /**
     * @return false|float
     */
    public function getOldPrice()
    {
        $defaultData = $this->getPriceData();
        return (float)$defaultData['price'] < (float)$defaultData['old_price']
            ? (float)$defaultData['old_price']
            : false;
    }

    /**
     * @return string
     */
    public function getDisplayOldPrice()
    {
        return $this->getOldPrice()
            ? '<span class="old-price "><span class="price-label">' . __('Regular Price') . '</span> '
                . '<span class="price-wrapper">' . $this->formatCurrency($this->getOldPrice()) . '</span></span>'
            : '';
    }

    /**
     * Retrieve price data from product and billing frequency.
     *
     * @return array|null
     */
    private function getPriceData()
    {
        return $this->getData('billing_frequency');
    }
}
