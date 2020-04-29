<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary;

/**
 * Subscription Email Overview block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailOverview extends \Magento\Framework\View\Element\Template
{
    /**
     * Init child block
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function initChildBlock(\Magento\Framework\View\Element\AbstractBlock $block)
    {
        $block->addData([
            'subscription_profiles' => $this->getSubscriptionProfiles()
        ]);

        return $block;
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        foreach ($this->getChildNames() as $names) {
            $this->initChildBlock($this->getLayout()->getBlock($names));
        }
        return parent::_prepareLayout();
    }

    /**
     * Returns shipping info block html.
     *
     * @return string
     */
    public function getShippingInfoHtml()
    {
        return $this->getChildHtml('shipping-information');
    }

    /**
     * @return string
     */
    public function getShippingDetailsHtml()
    {
        return $this->getChildHtml('shipping-details');
    }

    /**
     * Check if it necessary to show shipping details block.
     *
     * @return bool
     */
    public function canShowShippingDetailsBlock()
    {
        $canShow = false;
        foreach ($this->getSubscriptionProfiles() as $profile) {
            if (!(bool)$profile->getIsVirtual()) {
                $canShow = true;
            }
        }
        return $canShow;
    }

    /**
     * Returns billing info block html.
     *
     * @return string
     */
    public function getBillingInfoHtml()
    {
        return $this->getChildHtml('billing-information');
    }

    /**
     * Returns payment details block html.
     *
     * @return string
     */
    public function getPaymentDetailsHtml()
    {
        return $this->getChildHtml('payment-details');
    }

    /**
     * Returns products block html.
     *
     * @return string
     */
    public function getProductsHtml()
    {
        return $this->getChildHtml('products');
    }
}
