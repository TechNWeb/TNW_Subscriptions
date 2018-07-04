<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Pricing\Render;

use Magento\Framework\DataObject;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\Render\PriceBox as BasePriceBox;
use Magento\Framework\Pricing\Render\RendererPool;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as FrequencyOptionRepository;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\Product\Attribute as SubscriptionProductAttributes;
use TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager\TypeInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Class for subscription_price rendering
 *
 */
class SubscriptionPriceBox extends BasePriceBox
{
    /**
     * PriceCalculator
     *
     * @var PriceCalculator
     */
    private $priceCalculator;

    /**
     * Billing frequency option repository.
     *
     * @var FrequencyOptionRepository
     */
    private $frequencyOptionRepository;

    /**
     * @var TrialLengthUnitType
     */
    private $trialLengthUnitType;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * @var ProfileManager;
     */
    private $profileManager;

    /**
     * @var ProductTypeManagerResolver
     */
    private $productTypeResolver;

    /**
     * @param Template\Context $context
     * @param SaleableInterface $saleableItem
     * @param PriceInterface $price
     * @param RendererPool $rendererPool
     * @param FrequencyOptionRepository $frequencyOptionRepository
     * @param PriceCalculator $priceCalculator
     * @param TrialLengthUnitType $trialLengthUnitType
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param DescriptionCreator $descriptionCreator
     * @param ProfileManager $profileManager
     * @param ProductTypeManagerResolver $productTypeResolver
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SaleableInterface $saleableItem,
        PriceInterface $price,
        RendererPool $rendererPool,
        FrequencyOptionRepository $frequencyOptionRepository,
        PriceCalculator $priceCalculator,
        TrialLengthUnitType $trialLengthUnitType,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        DescriptionCreator $descriptionCreator,
        ProfileManager $profileManager,
        ProductTypeManagerResolver $productTypeResolver,
        array $data = []
    ) {
        parent::__construct($context, $saleableItem, $price, $rendererPool, $data);

        $this->frequencyOptionRepository = $frequencyOptionRepository;
        $this->priceCalculator = $priceCalculator;
        $this->trialLengthUnitType = $trialLengthUnitType;
        $this->priceCurrency = $priceCurrency;
        $this->jsonHelper = $jsonHelper;
        $this->descriptionCreator = $descriptionCreator;
        $this->profileManager = $profileManager;
        $this->productTypeResolver = $productTypeResolver;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        // Check catalog permissions
        if ($this->getSaleableItem()->getCanShowPrice() === false) {
            return '';
        }

        $result = parent::_toHtml();

        return $this->wrapResult($result);
    }

    /**
     * Wrap with standard required container.
     *
     * @param string $html
     * @return string
     */
    protected function wrapResult($html)
    {
        return '<div class="price-box ' . $this->getData('css_classes') . '" ' .
            'data-role="priceBox" ' .
            'data-product-id="' . $this->getSaleableItem()->getId() . '"' .
            '>' . $html . '</div>';
    }

    /**
     * Get Key for caching block content.
     *
     * @return string
     */
    public function getCacheKey()
    {
        return parent::getCacheKey() . ($this->getData('list_category_page') ? '-list-category-page': '');
    }

    /**
     * Returns list of product billing frequencies.
     *
     * @param DataObject $product
     * @return array
     */
    public function getProductBillingFrequencies(DataObject $product)
    {
        $result = [];

        if ($product) {
            /** @var TypeInterface $productTypeManager */
            $productTypeManager = $this->productTypeResolver->resolve($product->getTypeId());

            $trialPriceStatus = $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_STATUS);

            $productBillingFrequencies = $this->frequencyOptionRepository
                ->getListByProductId($product->getId())
                ->getItems();

            /** @var ProductBillingFrequencyInterface $billingFrequency */
            foreach ($productBillingFrequencies as $billingFrequency) {
                $billingFrequencyId = $billingFrequency->getBillingFrequencyId();

                $existFrequency = true;

                if ($product->getId() != $product->getChildProductId()) {
                    $existFrequency = $productTypeManager
                        ->checkFrequencyExistanse($billingFrequencyId, $product->getData('child_product_id'));
                }

                if ($existFrequency) {
                    $price = $this->priceCalculator->getUnitPrice($product, $billingFrequencyId);

                    $topMessage = '';
                    $bottomMessage = '';

                    $frequencyUnit = $this->descriptionCreator->getFrequencyWithUnit($billingFrequencyId);
                    $frequencyUnitMessage = '';

                    $isDefault = $billingFrequency->getDefaultBillingFrequency();
                    $initialFee = $this->getInitialFee($billingFrequency, $product);
                    if ($trialPriceStatus) {
                        $trialPrice = $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_PRICE);
                        $trialPeriod = $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_LENGTH);
                        $trialUnitId = $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_LENGTH_UNIT);

                        $topMessage = __('Try for %1', $this->getFrequencyTrialWithUnit($trialPeriod, $trialUnitId));
                        $bottomMessage = __('then %1 / every %2', $this->formatCurrency($price, false), $frequencyUnit);
                        $price = $trialPrice + $initialFee;
                    } elseif ($initialFee) {
                            $customPrice = $this->formatCurrency($price, false);
                            $topMessage = __('Initial charge');
                            $price = (float)$price + $initialFee;
                            $bottomMessage = __('then %1 / every %2', $customPrice, $frequencyUnit);
                    } else {
                        $frequencyUnitMessage = __(' / every %1', $frequencyUnit);
                    }

                    $result[$billingFrequencyId] = [
                        'default' => $isDefault,
                        'billing_frequency_id' => $billingFrequencyId,
                        'price' => $price,
                        'frequency_unit_message' => $frequencyUnitMessage,
                        'top_message' => $topMessage,
                        'bottom_message' => $bottomMessage,
                        'trial_price_status' => $trialPriceStatus
                    ];
                }
            }
        }

        return $result;
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
            $product->getChildProductId(),
            false
        );
    }

    /**
     * Retrieve current product model
     *
     * @return SaleableInterface
     */
    public function getProduct()
    {
        return $this->getSaleableItem();
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
        $unitLabel = $this->trialLengthUnitType->getLabelByValueAndLength((int)$unitId, $period);

        return strtolower($period . ' ' . $unitLabel);
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
     * Render subscription price amount blocks.
     *
     * @param AmountInterface $amount
     * @param SaleableInterface $product
     * @param array $arguments
     * @return array
     */
    public function renderSubscriptionAmounts(
        AmountInterface $amount,
        SaleableInterface $product,
        array $arguments = []
    ) {
        $result = [];
        if ($product === null) {
            $product = $this->getProduct();
        }
        $productData = $this->productTypeResolver
            ->resolve($product->getTypeId())
            ->getProductDataObject($product, $arguments);
        foreach ($this->getProductBillingFrequencies($productData) as $key => $billingFrequencyData) {
            $currentArguments['billing_frequency'] = array_replace($billingFrequencyData, $arguments);
            $resultAmount = parent::renderAmount($amount, $currentArguments);
            $result[$key] = $resultAmount;
            if (!empty($arguments['configurable_mapping'])) {
                $result[$key] = [
                    'amount' => $resultAmount,
                    'frequency_price' => $billingFrequencyData['price'],
                ];
            }
        }

        return $result;
    }

    /**
     * Get encoded billing frequency Id.
     *
     * @return string
     */
    public function getBillingFrequencyId()
    {
        $value = 0;
        if ($this->profileManager->getProfile()->getId()) {
            $value = $this->profileManager->getProfile()->getBillingFrequencyId();
        }

        return $this->getEncodedData(['value' => $value]);
    }

    /**
     * Return encoded to JSON format data.
     *
     * @param mixed $data
     * @return string
     */
    public function getEncodedData($data)
    {
        return $this->jsonHelper->jsonEncode($data);
    }
}
