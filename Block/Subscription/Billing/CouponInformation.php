<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Billing;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Block\Subscription\Info\ContentAbstract;
use TNW\Subscriptions\Model\MessagePool;
use Magento\SalesRule\Model\Utility;
use Magento\SalesRule\Model\Rule;
use TNW\Subscriptions\Api\SubscriptionProfileOrderRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context as FormContext;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

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
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * @var FormContext
     */
    private $formContext;
    /**
     * CouponInformation constructor.
     * @param Context $context
     * @param Registry $registry
     * @param MessagePool $messagePool
     * @param Utility $utility
     * @param Rule $salesRule
     * @param ProfileManager $profileManager
     * @param SubscriptionProfileOrderRepositoryInterface $subscriptionProfileOrderRepository
     * @param QuoteRepository $quoteRepository
     * @param Coupon $coupon
     * @param RuleResource $ruleResource
     * @param FormContext $formContext
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        MessagePool $messagePool,
        Utility $utility,
        Rule $salesRule,
        ProfileManager $profileManager,
        SubscriptionProfileOrderRepositoryInterface $subscriptionProfileOrderRepository,
        QuoteRepository $quoteRepository,
        Coupon $coupon,
        RuleResource $ruleResource,
        FormContext $formContext,
        array $data = []
    ) {
        $this->couponUtility = $utility;
        $this->salesRule = $salesRule;
        $this->profileManager = $profileManager;
        $this->subscriptionProfileOrderRepository = $subscriptionProfileOrderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->coupon = $coupon;
        $this->ruleResource = $ruleResource;
        $this->formContext = $formContext;
        parent::__construct($context, $registry, $messagePool, $data);
    }

    /**
     * Gets coupon code from profile or quote (during subscription creation)
     * @return string|null
     */
    public function getCouponCode()
    {
        $subscriptionProfile = $this->getSubscriptionProfile();
        if ($subscriptionProfile) {
            return $subscriptionProfile->getCouponCode();
        }
        return $this->getSubQuote()->getCouponCode();
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

                if ($this->getSubscriptionProfile()) {
                    $quote = $this->profileManager->getTempQuote($this->getSubscriptionProfile());
                } elseif ($this->getSubQuote()) {
                    $quote = $this->getSubQuote();
                } else {
                    return false;
                }
                $validForShippingAddress = $this->couponUtility->canProcessRule(
                    $this->salesRule,
                    $quote->getShippingAddress()
                );
                $billingAddress = $quote->getBillingAddress();
                $billingAddress['payment_method'] = $this->getSubscriptionProfile()->getPayment()->getEngineCode();
                $validForBillingAddress = $this->couponUtility->canProcessRule(
                    $this->salesRule,
                    $billingAddress
                );
                return $validForShippingAddress || $validForBillingAddress;
            } catch (\Exception $exception) {
                return false;
            }
        }
        return false;
    }

    /**
     * @return SubscriptionProfileInterface
     */
    public function getSubscriptionProfile()
    {
        return $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY)
            ?? parent::getSubscriptionProfile();
    }

    /**
     * @return false|Quote
     */
    public function getSubQuote()
    {
        $subQuotes = $this->formContext->getSession()->getSubQuotes();
        return reset($subQuotes);
    }
}
