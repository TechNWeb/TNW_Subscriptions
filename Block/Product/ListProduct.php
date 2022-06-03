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
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\Url\Helper\Data;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as FrequencyOptionRepository;
use TNW\Subscriptions\Block\Product\ListProduct\ListProductButtons;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Model\Config\Product\SubscriptionProductView;
use Magento\Catalog\Model\Product\Type;
use Magento\Downloadable\Model\Product\Type as DownloadableType;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;

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
     * @var CurrencyFactory
     */
    private $currencyFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductTypeManagerResolver
     */
    private $typeManagerResolver;

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
     * @param CurrencyFactory $currencyFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductTypeManagerResolver $typeManagerResolver
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
        CurrencyFactory $currencyFactory,
        StoreManagerInterface $storeManager,
        ProductTypeManagerResolver $typeManagerResolver,
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
        $this->currencyFactory = $currencyFactory;
        $this->storeManager = $storeManager;
        $this->typeManagerResolver = $typeManagerResolver;
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
     * @throws LocalizedException
     */
    public function getButtonsHtml($dataPostButton = false)
    {
        $buyButtonsBlock = $this->getLayout()->createBlock(
            ListProductButtons::class,
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
     * @return Phrase|string
     */
    public function getTopMessage($product)
    {
        $productArray = $this->getBestMatchProductDataObject($product);
        if (!$this->isAllowedProductType($product) || !$productArray) {
            return '';
        }
        if (isset($productArray[Attribute::SUBSCRIPTION_TRIAL_STATUS])
            && $productArray[Attribute::SUBSCRIPTION_TRIAL_STATUS] != 0
            && $productArray[Attribute::SUBSCRIPTION_PURCHASE_TYPE] != PurchaseType::ONE_TIME_PURCHASE_TYPE
            && $this->isProductTrialAvailableForCurrentCustomer($product)
        ) {
            $topMessage = __('Try for %1', $this->getFrequencyTrialWithUnit(
                $productArray[Attribute::SUBSCRIPTION_TRIAL_LENGTH],
                $productArray[Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT]
            ));
        } else {
            $topMessage = '';
        }
        return $topMessage;
    }

    /**
     * @param $product
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPriceLabel($product)
    {
        if ($product->getTypeId() === Configurable::TYPE_CODE && $this->getTopMessage($product) === '') {
            return sprintf('<span class="price-label">%s</span>', __('As low as'));
        }
        return '';
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
     * @throws LocalizedException
     */
    public function getTrialPriceForCategory($product)
    {
        $productBillingFrequencies = $this->frequencyOptionRepository
            ->getListByProductId($product->getId())
            ->getItems();

        $productData = $this->getBestMatchProductDataObject($product);
        if (empty($productBillingFrequencies)
            || !$productData
            || $product->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE)
                == PurchaseType::ONE_TIME_PURCHASE_TYPE
        ) {
            return $product->getTypeId() === Grouped::TYPE_CODE
                ? null
                : $this->formatCurrency(
                    $this->getConvertedPrice($product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue())
                );
        }

        $trialAllowed = $this->isProductTrialAvailableForCurrentCustomer($product);
        $trialStatus = !$trialAllowed ? 0 : $productData->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS);
        $initialFee = $this->getInitialFee($productData->getData('matched_billing_frequency'), $productData);
        $subscriptionPrice = (float)$this->priceCalculator->getUnitPrice(
            $productData,
            $productData->getData('matched_billing_frequency')->getBillingFrequencyId(),
            null,
            $trialAllowed
        );
        $price = $subscriptionPrice + $initialFee;
        if ($trialStatus == 1 && $price == 0) {
            $result = sprintf('<span class="free">%s</span>', __('Free'));
        } else {
            $result = $this->formatCurrency($this->getConvertedPrice($price));
        }

        return $result;
    }

    /**
     * @param $product
     * @return DataObject
     */
    public function getBestMatchProductDataObject($product)
    {
        try {
            $productBillingFrequencies = $this->frequencyOptionRepository
                ->getListByProductId($product->getId())
                ->getItems();
        } catch (LocalizedException $e) {
            $productBillingFrequencies = [];
        }
        $trialAllowed = $this->isProductTrialAvailableForCurrentCustomer($product);
        $products = $product->getTypeId() === Configurable::TYPE_CODE
            ? $product->getTypeInstance()->getUsedProducts($product, null)
            : [$product];
        $typeManager = $this->typeManagerResolver->resolve($product->getTypeId());
        $bestMatch = null;
        foreach ($products as $childProduct) {
            $productData = $typeManager->getProductDataObject($product, ['child_product' => $childProduct]);
            foreach ($productBillingFrequencies as $productBillingFrequency) {
                if ($productBillingFrequency['default_billing_frequency'] != 1) {
                    continue;
                }
                $billingFrequencyId = $productBillingFrequency->getBillingFrequencyId();
                if ($typeManager->checkFrequencyExistanse(
                    $billingFrequencyId,
                    $productData->getData('child_product_id')
                )
                ) {
                    try {
                        if (null === $bestMatch
                            || (float)$this->priceCalculator->getUnitPrice(
                                $bestMatch,
                                $billingFrequencyId,
                                null,
                                $trialAllowed
                            )
                            > (float)$this->priceCalculator->getUnitPrice(
                                $productData,
                                $billingFrequencyId,
                                null,
                                $trialAllowed
                            )
                        ) {
                            $bestMatch = $productData;
                            $bestMatch->setData('matched_billing_frequency', $productBillingFrequency);
                        }
                    } catch (NoSuchEntityException $e) {
                        continue;
                    }
                }
            }
        }
        return $bestMatch;
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
        $purchaseType = $this->typeManagerResolver->resolve($product->getTypeId())->getProductDataObject($product)
            ->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE);
        if ($purchaseType == PurchaseType::RECURRING_PURCHASE_TYPE
            || $purchaseType == PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE
        ) {
            return $this->productView->getCustomerGroupLimitation($product)
                && $product->getTypeId() !== Grouped::TYPE_CODE;
        }
        return false;
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
            || $product->getTypeId() == Configurable::TYPE_CODE
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

    /**
     * Convert price from base currency to current currency
     *
     * @param $price
     * @return float|int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getConvertedPrice($price)
    {
        $currentCurrency = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        $baseCurrency = $this->storeManager->getStore()->getBaseCurrency()->getCode();
        $rate = $this->currencyFactory->create()->load($baseCurrency)->getAnyRate($currentCurrency);
        return $price * $rate;
    }

    /**
     * Identities array is empty, because parent block returns needed product identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [];
    }
}
