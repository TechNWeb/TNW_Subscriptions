<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager;

use Magento\Bundle\Model\Product\Price;
use Magento\Bundle\Model\Product\Type;
use Magento\Bundle\Pricing\Price\FinalPrice;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Quote\Api\Data\CartItemInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as ProductFrequencyRepository;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Source\PriceStrategy;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Service\Serializer;

/**
 * Bundle product manager.
 */
class Bundle extends Base
{
    /**
     * @var ArrayUtils
     */
    private $arrayUtility;

    public function __construct(
        Config $config,
        PriceCalculator $priceCalculator,
        ProductFrequencyRepository $productFrequencyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        Serializer $serializer,
        ArrayUtils $arrayUtility
    ) {
        parent::__construct(
            $config,
            $priceCalculator,
            $productFrequencyRepository,
            $searchCriteriaBuilder,
            $productRepository,
            $serializer
        );
        $this->arrayUtility = $arrayUtility;
    }

    /**
     * @inheritDoc
     */
    public function modifyBuyRequests(array $products)
    {
    }

    /**
     * @inheritdoc
     */
    public function getProductDataObject(SaleableInterface $product, array $arguments = null)
    {
        $productData = parent::getProductDataObject($product, $arguments);
        $productId = $product->getId();
        /** @var FinalPrice $bundlePrice */
        $bundlePrice = $product->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE);
        $minimalFinalPrice = $bundlePrice->getMinimalPrice()->getValue();
        $data = [
            'child_product_id' => $productId,
            'final_minimal_price' => $minimalFinalPrice,
            'child_product_price' => $this->productRepository->getById($productId)->getFinalPrice(),
            'price_type' => $product->getPriceType(),
        ];
        $productData->addData($data);

        return $productData;
    }

    /**
     * @inheritdoc
     */
    protected function getCalculatedPrice(
        DataObject $product,
        array $productData,
        $full = false,
        $rowPrice = false
    ) {
        $productQty  = !empty($productData['qty']) ? $productData['qty'] : 0;
        $usePresetQty = !empty($productData['use_preset_qty']) && $productQty;
        $full = !empty($productData['modify_profile']) && $productData['modify_profile']
            ? false
            : $full;
        //Calculate product Price
        $lockProductPriceStatus =
            (bool) $product->getData(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE);
        $price = (int)$product['price_type'] === Price::PRICE_TYPE_FIXED
            ? (float)$this->priceCalculator->getUnitPrice(
                $product,
                $productData['billing_frequency'],
                isset($productData['price'])
                    ? $productData['price'] - $this->getBundleOptionsPrice($product, $productData)
                    : null,
                $full
            )
            : 0;
        $price += $this->getBundleOptionsPrice($product, $productData);

        if ($this->profileProduct) {
            $originProfileProductData = $this->profileProduct->getOrigData();
            $originUnitPrice = (float) $originProfileProductData['price'];
            $currentProfile = $this->getProfile();
            if ($this->config->getPricingStrategy() == PriceStrategy::GRANDFATHERED_PRICE
                && $currentProfile
                && $currentProfile->getOrigData('billing_frequency_id')
                    == $currentProfile->getData('billing_frequency_id')
                && $originProfileProductData['qty'] != $productData['qty']
            ) {
                $price = min($originUnitPrice, $price);
            }
        }
        if ($lockProductPriceStatus && $productQty) {
            $tierPrice = $this->productRepository
                ->getById($product->getData('child_product_id'))
                ->getTierPrice($productQty);
            if ($tierPrice) {
                $price = min($tierPrice, $price);
            }
        }
        if (!$rowPrice && $usePresetQty) {
            if (isset($productData['rebill_processing'])
                && $productData['rebill_processing']
            ) {
                $price = $productData['subscription_data']['non_unique']['current_preset_qty_price'];
            }
            $price = $productQty ? round($price, 4) : 0;
        }

        return $price;
    }

    /**
     * @param DataObject $product
     * @param array $productData
     * @return float|int
     * @throws NoSuchEntityException
     */
    public function getBundleOptionsPrice(DataObject $product, $productData)
    {
        $optionsPrice = 0;
        $productId = (int)$product->getId();
        $product = $this->productRepository->getById($productId);
        /** @var $typeInstance Type */
        $typeInstance = $product->getTypeInstance();
        $options = $productData['bundle_option'] ?? null;
        $qtys = $productData['bundle_option_qty'] ?? null;
        if (!$options && $this->profileProduct) {
            $options = $this->profileProduct->getCustomOptions()['info_buyRequest']['bundle_option'] ?? null;
            $qtys = $this->profileProduct->getCustomOptions()['info_buyRequest']['bundle_option_qty'] ?? null;
        }
        if (is_array($options)) {
            $options = $this->recursiveIntval($options);
            $selectionIds = array_values($this->arrayUtility->flatten($options));
            if (!empty($selectionIds)) {
                $selections = $typeInstance->getSelectionsByIds($selectionIds, $product)->getItems();
            }
        }
        if (isset($selections) && is_array($selections)) {
            foreach ($selections as $selection) {
                $selectionOptionId = $selection->getOptionId();
                if ($selection->getSelectionCanChangeQty() && isset($qtys[$selectionOptionId])) {
                    $qty = (float)$qtys[$selectionOptionId] > 0 ? $qtys[$selectionOptionId] : 1;
                } else {
                    $qty = (float)$selection->getSelectionQty() ? $selection->getSelectionQty() : 1;
                }
                $price = $product->getPriceModel()
                    ->getSelectionFinalTotalPrice($product, $selection, 0, 1);
                $optionsPrice += $price * $qty;
            }
        }
        return $optionsPrice;
    }

    /**
     * @param $productData
     * @return float
     */
    public function getOrigBundleOptionsPrice($productData)
    {
        $optionsPrice = 0.;
        $options = $this->serializer->unserialize($productData['custom_options'])['bundle_options'] ?? [];
        foreach ($options as $option) {
            if (is_array($option['value'])) {
                foreach ($option['value'] as $value) {
                    $optionsPrice += $value['price'];
                }
            }
        }
        return $optionsPrice;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalData(CartItemInterface $item)
    {
        return [
            'bundle_option' => $item->getBuyRequest()->getBundleOption(),
            'bundle_option_qty' => $item->getBuyRequest()->getBundleOptionQty(),
        ];
    }

    /**
     * Cast array values to int
     *
     * @param array $array
     * @return int[]|int[][]
     */
    private function recursiveIntval(array $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->recursiveIntval($value);
            } elseif (is_numeric($value) && (int)$value != 0) {
                $array[$key] = (int)$value;
            } else {
                unset($array[$key]);
            }
        }

        return $array;
    }
}
