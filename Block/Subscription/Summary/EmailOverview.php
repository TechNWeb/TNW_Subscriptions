<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Subscription Email Overview block
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailOverview extends \Magento\Framework\View\Element\Template
{
    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * @param Template\Context $context
     * @param SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
    }


    /**
     * Returns shipping info block html.
     *
     * @return string
     */
    public function getShippingInfoHtml()
    {
        return $this->getChildBlock('shipping-information')
            ->setData('subscription_profiles', $this->getSubscriptionProfiles())->toHtml();
    }

    /**
     * @return string
     */
    public function getShippingDetailsHtml()
    {
        return $this->getChildBlock('shipping-details')
            ->setData('subscription_profiles', $this->getSubscriptionProfiles())->toHtml();
    }

    /**
     * Check if it necessary to show shipping details block.
     *
     * @return bool
     */
    public function canShowShippingDetailsBlock()
    {
        $canShow = false;
        if (!$this->getSubscriptionProfiles()) {
            return false;
        }
        foreach ($this->getSubscriptionProfiles() as $profile) {
            if (!$profile->getIsVirtual()) {
                $canShow = true;
            }
        }
        return $canShow;
    }

    /**
     * @return SubscriptionProfile[]
     */
    public function getSubscriptionProfiles()
    {
        $profiles = null;
        if ($this->getData('subscription_profile')) {
            $profiles = [$this->getData('subscription_profile')];
        } else if ($this->getSubscriptionProfileId()) {
            try {
                $profiles = [$this->subscriptionProfileRepository->getById($this->getSubscriptionProfileId())];
            } catch (NoSuchEntityException $e) {
            }
        } else if ($this->getSubscriptionProfileIds() && is_array($this->getSubscriptionProfileIds())) {
            $profiles = [];
            foreach ($this->getSubscriptionProfileIds() as $id) {
                try {
                    $profiles[] = $this->subscriptionProfileRepository->getById($id);
                } catch (NoSuchEntityException $e) {
                    continue;
                }
            }
        }
        return $this->getData('subscription_profiles') ?? $profiles;
    }

    /**
     * Returns billing info block html.
     *
     * @return string
     */
    public function getBillingInfoHtml()
    {
        return $this->getChildBlock('billing-information')
            ->setData('subscription_profiles', $this->getSubscriptionProfiles())->toHtml();
    }

    /**
     * Returns payment details block html.
     *
     * @return string
     */
    public function getPaymentDetailsHtml()
    {
        return $this->getChildBlock('payment-details')
            ->setData('subscription_profiles', $this->getSubscriptionProfiles())->toHtml();
    }

    /**
     * Returns products block html.
     *
     * @return string
     */
    public function getProductsHtml()
    {
        return $this->getChildBlock('products')
            ->setData('subscription_profiles', $this->getSubscriptionProfiles())->toHtml();
    }

    /**
     * Return coupon block html
     */
    public function getCouponDetails()
    {
        return $this->getChildBlock('coupon-details')
            ->setData('subscription_profiles', $this->getSubscriptionProfiles())->toHtml();
    }
}
