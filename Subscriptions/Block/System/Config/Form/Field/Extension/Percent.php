<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\System\Config\Form\Field\Extension;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeCodeResolver;

/**
 * Percent/Price configuration field renderer.
 */
class Percent extends Currency
{
    /**
     * Setup element before render.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     */
    protected function setupElement(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        parent::setupElement($element);
    }
}
