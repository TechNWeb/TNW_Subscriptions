<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Billing;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use TNW\Subscriptions\Block\Subscription\Info\ContentAbstract;
use TNW\Subscriptions\Model\MessagePool;
use Magento\SalesRule\Model\Utility;
use Magento\SalesRule\Model\Rule;
use TNW\Subscriptions\Api\SubscriptionProfileOrderRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;

/**
 * Block for view used coupon code information
 */
class CouponInformation extends ContentAbstract
{
    /**
     * @var Utility
     */
    private $couponUtility;

    /**
     * @var Rule
     */
    private $salesRule;

    /**
     * @var SubscriptionProfileOrderRepositoryInterface
     */
    private $subscriptionProfileOrderRepository;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var Coupon
     */
    private $coupon;

    /**
     * @var RuleResource
     */
    private $ruleResource;

    /**
     * CouponInformation constructor.
     * @param Context $context
     * @param Registry $registry
     * @param MessagePool $messagePool
     * @param Utility $utility
     * @param Rule $salesRule
     * @param SubscriptionProfileOrderRepositoryInterface $subscriptionProfileOrderRepository
     * @param QuoteRepository $quoteRepository
     * @param Coupon $coupon
     * @param RuleResource $ruleResource
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        MessagePool $messagePool,
        Utility $utility,
        Rule $salesRule,
        SubscriptionProfileOrderRepositoryInterface $subscriptionProfileOrderRepository,
        QuoteRepository $quoteRepository,
        Coupon $coupon,
        RuleResource $ruleResource,
        array $data = []
    ) {
        $this->couponUtility = $utility;
        $this->salesRule = $salesRule;
        $this->subscriptionProfileOrderRepository = $subscriptionProfileOrderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->coupon = $coupon;
        $this->ruleResource = $ruleResource;
        parent::__construct($context, $registry, $messagePool, $data);
    }

    /**
     * @return mixed|string|null
     */
    public function getCouponCode()
    {
        return $this->getSubscriptionProfile()->getCouponCode();
    }

    /**
     * @return bool
     */
    public function isCouponValid()
    {
        if ($this->getCouponCode()) {
            try {
                $ruleId = $this->coupon->loadByCode($this->getCouponCode())->getRuleId();
                if (!$ruleId) {
                    return false;
                }
                $this->ruleResource->load($this->salesRule, $ruleId);
                if (!$this->salesRule->getIsActive()) {
                    return false;
                }
                $subscriptionProfileOrder = $this->subscriptionProfileOrderRepository->getById(
                    $this->getSubscriptionProfile()->getId()
                );
                $quote = $this->quoteRepository->get($subscriptionProfileOrder->getMagentoQuoteId());
                $quote->setCouponCode($this->getCouponCode());
                $validForShippingAddress = $this->couponUtility->canProcessRule(
                    $this->salesRule,
                    $quote->getShippingAddress()
                );
                $validForBillingAddress = $this->couponUtility->canProcessRule(
                    $this->salesRule,
                    $quote->getBillingAddress()
                );
                return $validForShippingAddress || $validForBillingAddress;
            } catch (\Exception $exception) {
                return false;
            }
        }
        return false;
    }
}
