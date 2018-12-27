<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Pricing\Render;

use Magento\Catalog\Helper\Product as HelperProduct;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\Render\RendererPool;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as FrequencyOptionRepository;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;

/**
 * Class for subscription price rendering for configurable products.
 */
class SubcsriptionConfigurablePriceBox extends SubscriptionPriceBox
{
    /**
     * @var HelperProduct
     */
    private $helperProduct;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * @var \Magento\Swatches\Helper\Data
     */
    private $swatchHelper;

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
     * @param HelperProduct $helperProduct
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Swatches\Helper\Data $swatchHelper
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
        HelperProduct $helperProduct,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Swatches\Helper\Data $swatchHelper,
        \TNW\Subscriptions\Model\Config\Product\SubscriptionProductView $subscriptionProductViewConfig,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $saleableItem,
            $price,
            $rendererPool,
            $frequencyOptionRepository,
            $priceCalculator,
            $trialLengthUnitType,
            $priceCurrency,
            $jsonHelper,
            $descriptionCreator,
            $profileManager,
            $productTypeResolver,
            $subscriptionProductViewConfig,
            $data
        );

        $this->helperProduct = $helperProduct;
        $this->moduleManager = $moduleManager;
        $this->swatchHelper = $swatchHelper;
    }

    /**
     * @inheritdoc
     */
    public function renderSubscriptionAmounts(
        AmountInterface $amount,
        SaleableInterface $currentProduct,
        array $arguments = []
    ) {
        $result = [];
        $mappedResult = [];
        $priceMap = [];
        $arguments['configurable_mapping'] = true;
        if ($currentProduct->getTypeId() === Configurable::TYPE_CODE) {
            $mappedResult['config'] = parent::renderSubscriptionAmounts($amount, $currentProduct, $arguments);

            foreach ($this->getAllowProducts() as $product) {
                /** @var \Magento\Framework\Pricing\Price\PriceInterface $simpleProductAmount */
                $simpleProductAmount = $product->getPriceInfo()->getPrice('final_price');
                $arguments['child_product'] = $product;
                $mappedResult[$product->getId()] = parent::renderSubscriptionAmounts(
                    $simpleProductAmount->getAmount(),
                    $currentProduct,
                    $arguments
                );
            }
        }

        foreach ($mappedResult as $key => $productsAmount) {
            if (!empty($productsAmount)) {
                foreach ($productsAmount as $frequencyId => $data) {
                    $priceMap[$frequencyId][$key] = $data['frequency_price'];
                    $result[$key][$frequencyId] = $data['amount'];
                }
            } else {
                $result[$key] = [];
            }
        }

        $configurableDefaults = [];
        foreach ($priceMap as $frequencyId => $productPrices) {
            $minPrice = array_filter($productPrices);
            $minPrices = array_keys($productPrices, min($minPrice));
            $configurableDefaults[$frequencyId] = !empty($minPrices) ? reset($minPrices) : 'config';
        }
        $result['default'] = $configurableDefaults;

        return $result;
    }

    /**
     * Get Allowed Products
     *
     * @return \Magento\Catalog\Model\Product[]
     */
    private function getAllowProducts()
    {
        if (!$this->hasAllowProducts()) {
            $skipSaleableCheck = $this->helperProduct->getSkipSaleableCheck();

            $products = $skipSaleableCheck ?
                $this->getProduct()->getTypeInstance()->getUsedProducts($this->getProduct(), null) :
                $this->getProduct()->getTypeInstance()->getSalableUsedProducts($this->getProduct(), null);
            $this->setAllowProducts($products);
        }

        return $this->getData('allow_products');
    }

    /**
     * Return product super attributes available options.
     *
     * @return array
     */
    public function getOptions()
    {
        $options = [];
        $currentProduct = $this->getProduct();
        if ($currentProduct->getTypeId() === Configurable::TYPE_CODE) {
            $allowedProducts = $this->getAllowProducts();
            $configurableAttributes = $currentProduct->getTypeInstance()->getConfigurableAttributes($currentProduct);

            foreach ($allowedProducts as $product) {
                $productId = $product->getId();
                foreach ($configurableAttributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $productAttributeId = $productAttribute->getId();
                    $attributeValue = $product->getData($productAttribute->getAttributeCode());

                    $options[$productId][$productAttributeId] = $attributeValue;
                }
            }
        }

        $defaultValues = $this->getRequest()->getParam('attributes');

        if (!empty($defaultValues) && is_array($defaultValues)) {
            $options['defaultValues'] = $defaultValues;
        }

        return $options;
    }

    /**
     * Check if swatch attributes will be shown.
     *
     * @return bool
     */
    public function productHasSwatch()
    {
        $result = false;

        if ($this->moduleManager->isOutputEnabled('Magento_Swatches')) {
            $product = $this->getProduct();

            $result = $this->swatchHelper->isProductHasSwatch($product);
        }

        return $result;
    }
}
