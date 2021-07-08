<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as FrequencyRepository;
use TNW\Subscriptions\Api\CustomerProductHistoryManagementInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as FrequencyOptionRepository;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Product\SubscriptionProductView;
use TNW\Subscriptions\Model\Context as SubscriptionContext;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\ProductBillingFrequency\SavingsCalculation;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Subscribe product block instance on edit product additional data page.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 */
class Edit extends \TNW\Subscriptions\Block\Product\View\Subscribe
{
    /**
     * Subscription profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * @var SubscriptionProfileInterface;
     */
    private $subscriptionProfile;

    /**
     * Edit constructor.
     * @param Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param SubscriptionProductView $subscriptionProductViewConfig
     * @param Config $config
     * @param FrequencyOptionRepository $frequencyOptionRepository
     * @param FrequencyRepository $frequencyRepository
     * @param SavingsCalculation $savingsCalculation
     * @param ProductTypeManagerResolver $subscriptionTypeResolver
     * @param PriceCalculator $priceCalculator
     * @param Config\Source\TrialLengthUnitType $trialLengthUnitType
     * @param ProfileManager $profileManager
     * @param SubscriptionContext $subscriptionContext
     * @param CustomerProductHistoryManagementInterface $customerProductHistoryManagement
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        SubscriptionProductView $subscriptionProductViewConfig,
        Config $config,
        FrequencyOptionRepository $frequencyOptionRepository,
        FrequencyRepository $frequencyRepository,
        SavingsCalculation $savingsCalculation,
        ProductTypeManagerResolver $subscriptionTypeResolver,
        PriceCalculator $priceCalculator,
        Config\Source\TrialLengthUnitType $trialLengthUnitType,
        ProfileManager $profileManager,
        SubscriptionContext $subscriptionContext,
        CustomerProductHistoryManagementInterface $customerProductHistoryManagement,
        array $data = []
    ) {
        $this->profileManager = $profileManager;
        parent::__construct($context, $urlEncoder, $jsonEncoder, $string, $productHelper, $productTypeConfig,
            $localeFormat, $customerSession, $productRepository, $priceCurrency, $subscriptionProductViewConfig,
            $config, $frequencyOptionRepository, $frequencyRepository, $savingsCalculation, $subscriptionTypeResolver,
            $priceCalculator, $trialLengthUnitType, $subscriptionContext, $customerProductHistoryManagement, $data);
    }

    /**
     * Return subscription product Id.
     *
     * @return string
     */
    public function getSubscriptionProductId()
    {
        $subProduct = $this->getSubscriptionProduct();

        return $subProduct ? $subProduct->getId() : '';
    }

    /**
     * Return subscription product.
     *
     * @return \TNW\Subscriptions\Model\ProductSubscriptionProfile|null
     */
    public function getSubscriptionProduct()
    {
        return $this->_coreRegistry->registry('tnw_subscription_product');
    }

    /**
     * Return subscription profile from subscription product.
     *
     * @return SubscriptionProfileInterface|bool
     */
    private function getSubscriptionProfile()
    {
        if ($this->subscriptionProfile === null) {
            $subProduct = $this->getSubscriptionProduct();
            $profileId = $subProduct ? $subProduct->getSubscriptionProfileId() : 0;
            $this->subscriptionProfile = false;
            if ($profileId !== 0) {
                $this->subscriptionProfile = $this->profileManager->loadProfile($profileId);
            }
        }

        return $this->subscriptionProfile;
    }

    /**
     * Return subscription profile Billing frequency Id.
     *
     * @return bool|string
     */
    public function getBillingFrequencyId()
    {
        return $this->getSubscriptionProfile()
            ? $this->getSubscriptionProfile()->getBillingFrequencyId()
            : false;
    }

    /**
     * Return current subscription profile billing frequency data.
     *
     * @return array
     */
    public function getCurrentFrequencyData()
    {
        $data = [];
        $subFrequencyId = $this->getBillingFrequencyId();
        $frequencyOptions = $this->getFrequencyOptions();

        foreach ($frequencyOptions as $option) {
            if (!$subFrequencyId) {
                $subFrequencyId = ((int)$option['is_default'] === 1) ? $option['value'] : 0;
            }
            if ($subFrequencyId && $subFrequencyId == $option['value']) {
                $data = $option;
                break;
            }
        }

        return $data;
    }

    /**
     * Return subscription profile period label.
     *
     * @return string|\Magento\Framework\Phrase
     */
    public function getSubscriptionPeriodLabel()
    {
        $label = '';
        $profile = $this->getSubscriptionProfile();
        if ($profile) {
            if ($profile->getTerm()) {
                $label = __('Until canceled');
            } elseif ((int)$profile->getTotalBillingCycles() === 1) {
                $label = __('Bill once');
            } else {
                $label = __('Bill %1 times', $profile->getTotalBillingCycles());
            }
        }

        return $label;
    }

    /**
     * Return subscription profile start date.
     *
     * @return string
     */
    public function getStartOn()
    {
        $startOn = '';
        $profile = $this->getSubscriptionProfile();
        if ($profile) {
            $trialStartDate = $profile->getTrialStartDate();
            $startOn = isset($trialStartDate) ? $trialStartDate : $profile->getStartDate();
        }

        return $this->_localeDate->formatDate(new \DateTime($startOn), \IntlDateFormatter::SHORT);
    }

    /**
     * Return subscription product qty.
     *
     * @return int
     */
    public function getSubProductQty()
    {
        return $this->getSubscriptionProduct()
            ? $this->getSubscriptionProduct()->getQty() * 1
            : $this->getDefaultSubscribeQty();
    }
}
