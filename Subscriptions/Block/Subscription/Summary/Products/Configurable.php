<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary\Products;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ManagerConfigurable;

/**
 * Configurable products additional data on Account Dashboard in Summary tab.
 *
 * @method ProductSubscriptionProfile getItem()
 */
class Configurable extends Template
{
    /**
     * @var ManagerConfigurable
     */
    private $managerConfigurable;

    /**
     * @param Template\Context $context
     * @param ManagerConfigurable $managerConfigurable
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ManagerConfigurable $managerConfigurable,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->managerConfigurable = $managerConfigurable;
    }

    /**
     * Return subscription profile configurable product custom options data.
     *
     * @return array
     */
    public function getItemOptions()
    {
        return $this->managerConfigurable->getConfigurableOptionsData($this->getItem());
    }
}
