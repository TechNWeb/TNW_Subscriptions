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
            ? '<span class="old-price main"><span class="price-label">' . __('Regular Price') . '</span> '
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

    /**
     * Show bundle price box as range or not
     * @return bool
     */
    public function showRangePrice()
    {
        $defaultData = $this->getPriceData();
        return isset($defaultData['show_range_price']) && $defaultData['show_range_price'];
    }

    /**
     * @return false|float
     */
    public function getMinimalPrice()
    {
        $defaultData = $this->getPriceData();
        if (isset($defaultData['minimal_option_price']) && isset($defaultData['price'])) {
            return (float)$defaultData['minimal_option_price'] + (float)$defaultData['price'];
        }
        return false;
    }

    /**
     * @return string
     */
    public function getDisplayMinimalPrice()
    {
        if ($this->getMinimalPrice()) {
            return $this->formatCurrency($this->getMinimalPrice()) ;
        }
        return '';
    }

    /**
     * @return false|float
     */
    public function getMaximalPrice()
    {
        $defaultData = $this->getPriceData();
        if (isset($defaultData['maximal_option_price']) && isset($defaultData['price'])) {
            return (float)$defaultData['maximal_option_price'] + (float)$defaultData['price'];
        }
        return false;
    }

    /**
     * @return string
     */
    public function getDisplayMaximalPrice()
    {
        if ($this->getMaximalPrice()) {
            return $this->formatCurrency($this->getMaximalPrice());
        }
        return '';
    }

    /**
     * @return false|float
     */
    public function getDisplayMinimalOnetimePrice()
    {
        $defaultData = $this->getPriceData();
        if ($this->getMinimalPrice()
            && isset($defaultData['onetime_minimal_price'])
            && $this->getMinimalPrice() < (float)$defaultData['onetime_minimal_price']
        ) {
            return $this->formatCurrency($defaultData['onetime_minimal_price']);
        }
        return false;
    }

    /**
     * @return false|float
     */
    public function getDisplayMaximalOnetimePrice()
    {
        $defaultData = $this->getPriceData();
        if ($this->getMaximalPrice()
            && isset($defaultData['onetime_maximal_price'])
            && $this->getMaximalPrice() < (float)$defaultData['onetime_maximal_price']
        ) {
            return $this->formatCurrency($defaultData['onetime_maximal_price']);
        }
        return false;
    }
}
