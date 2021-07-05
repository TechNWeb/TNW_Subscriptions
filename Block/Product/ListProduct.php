<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ListProduct as OrigListProduct;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\Url\Helper\Data;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as FrequencyOptionRepository;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\Product\Attribute as SubscriptionProductAttributes;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Model\Config\Product\SubscriptionProductView;
use Magento\Catalog\Model\Product\Type;
use Magento\Downloadable\Model\Product\Type as DownloadableType;

/**
 *  Subscription Product list.
 */
class ListProduct extends OrigListProduct
{
    /**
     * Params witch added to button block.
     *
     * @var array
     */
    private $postParamsToButtonsBlock = [];

    /**
     * @var array
     */
    private $currentCustomerProductHistoryList = [];

    /**
     * @var TrialLengthUnitType
     */
    private $trialLengthUnitType;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var PriceCalculator
     */
    private $priceCalculator;

    /**
     * @var FrequencyOptionRepository
     */
    private $frequencyOptionRepository;

    /**
     * @var SubscriptionProductView
     */
    private $productView;

    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * ListProduct constructor.
     * @param Context $context
     * @param PostHelper $postDataHelper
     * @param Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Data $urlHelper
     * @param TrialLengthUnitType $trialLengthUnitType
     * @param PriceCurrencyInterface $priceCurrency
     * @param PriceCalculator $priceCalculator
     * @param FrequencyOptionRepository $frequencyOptionRepository
     * @param SubscriptionProductView $productView
     * @param EncoderInterface $urlEncoder
     * @param SerializerInterface $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        PostHelper $postDataHelper,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        Data $urlHelper,
        TrialLengthUnitType $trialLengthUnitType,
        PriceCurrencyInterface $priceCurrency,
        PriceCalculator $priceCalculator,
        FrequencyOptionRepository $frequencyOptionRepository,
        SubscriptionProductView $productView,
        EncoderInterface $urlEncoder,
        SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
        $this->trialLengthUnitType = $trialLengthUnitType;
        $this->priceCurrency = $priceCurrency;
        $this->priceCalculator = $priceCalculator;
        $this->frequencyOptionRepository = $frequencyOptionRepository;
        $this->productView = $productView;
        $this->urlEncoder = $urlEncoder;
        $this->serializer = $serializer;
    }

    /**
     * Prepare params too add button block.
     *
     * @param $product
     * @param String $pos
     * @param String $viewMode
     * @param String $position
     * @param array|string $postParams
     * @return void
     */
    public function prepareParamsToButtonsBlock($product, $pos, $viewMode, $position, $postParams)
    {
        if (empty($postParams)) {
            $postParams = $this->getAddToCartPostParams($product);
        }
        if (is_string($postParams)) {
            $postParams = $this->serializer->unserialize($postParams);
        }
        $this->postParamsToButtonsBlock = [
            'data' => [
                'product' => $product,
                'pos' => $pos,
                'view_mode' => $viewMode,
                'position' => $position,
                'post_params' => $postParams
            ]
        ];

        if (!empty($this->currentCustomerProductHistoryList)) {
            $this->postParamsToButtonsBlock['data']['customer_products_history_list'] =
                $this->currentCustomerProductHistoryList;
        }
    }

    /**
     * Get post parameters.
     *
     * @param Product $product
     * @return array
     */
    public function getAddToCartPostParams(Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data' => [
                'product' => $product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode($url),
            ]
        ];
    }

    /**
     * Create and return buttons block HTML with params.
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonsHtml($dataPostButton = false)
    {
        $buyButtonsBlock = $this->getLayout()->createBlock(
            \TNW\Subscriptions\Block\Product\ListProduct\ListProductButtons::class,
            $this->getNameInLayout() . '_' . $this->postParamsToButtonsBlock['data']['product']->getId(),
            $this->postParamsToButtonsBlock
        );
        return $dataPostButton
            ? $buyButtonsBlock->setTemplate('TNW_Subscriptions::product/list/post-buttons.phtml')->toHtml()
            : $buyButtonsBlock->setTemplate('TNW_Subscriptions::product/list/buttons.phtml')->toHtml();
    }

    /**
     * Get length of trial period
     *
     * @param $product
     * @return \Magento\Framework\Phrase|string
     */
    public function getTopMessage($product)
    {
        $productArray = $product->getData();
        if (isset($productArray['tnw_subscr_trial_status'])
            && $productArray['tnw_subscr_trial_status'] != 0
            && $productArray['tnw_subscr_purchase_type'] != PurchaseType::ONE_TIME_PURCHASE_TYPE
            && $this->isProductTrialAvailableForCurrentCustomer($product)
        ) {
            $topMessage = __('Try for %1', $this->getFrequencyTrialWithUnit(
                $productArray['tnw_subscr_trial_length'],
                $productArray['tnw_subscr_trial_length_unit']
            ));
        } else {
            $topMessage = '';
        }
        return $topMessage;
    }

    /**
     * Return Billing Frequency Trial with unit (e.g. "6 months")
     *
     * @param $period
     * @param $unitId
     * @return string
     */
    private function getFrequencyTrialWithUnit($period, $unitId)
    {
        return strtolower(
            $period . ' ' . $this->trialLengthUnitType->getLabelByValueAndLength((int) $unitId, $period)
        );
    }

    /**
     * Get price of trial period
     *
     * @param $product
     * @return float|string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTrialPriceForCategory($product)
    {
        $productBillingFrequencies = $this->frequencyOptionRepository
            ->getListByProductId($product->getId())
            ->getItems();

        if (empty($productBillingFrequencies)
            || $product->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE) == PurchaseType::ONE_TIME_PURCHASE_TYPE
        ) {
            return $product->getTypeId() === Grouped::TYPE_CODE
                ? null
                : $this->formatCurrency($product->getPrice(), false);
        }

        $result = null;
        $trialStatus = $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_STATUS);
        $trialPrice = $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_PRICE);
        if (!$this->isProductTrialAvailableForCurrentCustomer($product)) {
            $trialStatus = 0;
        }
        if ($trialStatus == 1) {
            $result = sprintf('<span class="free">%s</span>', __('Free'));
        }
        foreach ($productBillingFrequencies as $productBillingFrequency) {
            $initialFee = $this->getInitialFee($productBillingFrequency, $product);

            if ($trialStatus == 0 && $productBillingFrequency['default_billing_frequency'] == 1) {
                $subscriptionPrice = $productBillingFrequency['price'] + $initialFee;
                $result = $this->formatCurrency($subscriptionPrice, false);
            } elseif ($trialStatus == 1 && $productBillingFrequency['default_billing_frequency'] == 1) {
                $price = $trialPrice + $initialFee;
                $customPrice = $this->formatCurrency($price, false);

                if ($price != 0 && $trialStatus == 1) {
                    $result = $customPrice;
                }
            }

        }
        return $result;
    }

    /**
     * Format price value
     *
     * @param float $amount
     * @param bool $includeContainer
     * @param int $precision
     * @return float
     */
    public function formatCurrency(
        $amount,
        $includeContainer = true,
        $precision = PriceCurrencyInterface::DEFAULT_PRECISION
    ) {
        return $this->priceCurrency->format($amount, $includeContainer, $precision);
    }

    /**
     * Checking is subscription price
     *
     * @param $product
     * @return bool
     */
    public function isSubscriptionPrice($product)
    {
        return $this->productView->getCustomerGroupLimitation($product)
            && $product->getTypeId() !== Grouped::TYPE_CODE;
    }

    /**
     * Checking for allowed types
     *
     * @param $product
     * @return bool
     */
    public function isAllowedProductType($product)
    {
        return $product->getTypeId() == Type::TYPE_SIMPLE
            || $product->getTypeId() == Type::TYPE_VIRTUAL
            || $product->getTypeId() == Grouped::TYPE_CODE
            || $product->getTypeId() == DownloadableType::TYPE_DOWNLOADABLE;
    }

    /**
     * Set products history list for logged in customer
     *
     * @param array $products
     * @return void
     */
    public function setCurrentCustomerProductHistoryList(array $products)
    {
        $this->currentCustomerProductHistoryList = $products;
    }

    /**
     * Check is product trial available for current customer
     *
     * @param $product
     * @return bool
     */
    private function isProductTrialAvailableForCurrentCustomer($product)
    {
        return !in_array((int)$product->getId(), $this->currentCustomerProductHistoryList, true);
    }

    /**
     * Return product initial fee.
     *
     * @param ProductBillingFrequencyInterface $billingFrequency
     * @param DataObject $product
     * @return float
     */
    private function getInitialFee(ProductBillingFrequencyInterface $billingFrequency, DataObject $product)
    {
        return $this->priceCalculator->getInitialFee(
            $billingFrequency->getBillingFrequencyId(),
            $product->getId(),
            false
        );
    }
}
