<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\CyberSource\Tax\Model\Tax\Sales\Total\Quote;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterfaceFactory;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;

/**
 * Class Tax - fixes the cybersource notice error
 */
class Tax
{
    /**
     * @var TaxClassKeyInterfaceFactory
     */
    private $taxClassKeyDataObjectFactory;

    /**
     * @var mixed
     */
    private $taxHelper;

    /**
     * @var QuoteDetailsItemExtensionInterfaceFactory
     */
    private $quoteDetailsItemExtensionFactory;

    /**
     * Tax constructor.
     * @param TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory
     * @param QuoteDetailsItemExtensionInterfaceFactory $quoteDetailsItemExtensionFactory
     * @param TaxHelper|null $taxHelper
     */
    public function __construct(
        TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        QuoteDetailsItemExtensionInterfaceFactory $quoteDetailsItemExtensionFactory,
        ?TaxHelper $taxHelper = null
    ) {
        $this->taxClassKeyDataObjectFactory = $taxClassKeyDataObjectFactory;
        $this->quoteDetailsItemExtensionFactory = $quoteDetailsItemExtensionFactory;
        $this->taxHelper = $taxHelper ?: ObjectManager::getInstance()->get(TaxHelper::class);
    }

    /**
     * @param $subject
     * @param $proceed
     * @param QuoteDetailsItemInterfaceFactory $itemDataObjectFactory
     * @param AbstractItem $item
     * @param $priceIncludesTax
     * @param $useBaseCurrency
     * @param null $parentCode
     * @return QuoteDetailsItemInterface
     */
    public function aroundMapItem(
        $subject,
        $proceed,
        QuoteDetailsItemInterfaceFactory $itemDataObjectFactory,
        AbstractItem $item,
        $priceIncludesTax,
        $useBaseCurrency,
        $parentCode = null
    ) {
        if (class_exists('\CyberSource\Tax\Model\Tax\Sales\Total\Quote\Tax')) {
            $classType = \CyberSource\Tax\Model\Tax\Sales\Total\Quote\Tax::class;
            if ($subject instanceof $classType) {
                try {
                    $itemDataObject = $proceed(
                        $itemDataObjectFactory,
                        $item,
                        $priceIncludesTax,
                        $useBaseCurrency,
                        $parentCode
                    );
                } catch (\Exception $e) {
                    $itemDataObject = $this->mapItem(
                        $itemDataObjectFactory,
                        $item,
                        $priceIncludesTax,
                        $useBaseCurrency,
                        $parentCode
                    );
                }
                return $itemDataObject;
            }
        }
        return $proceed($itemDataObjectFactory, $item, $priceIncludesTax, $useBaseCurrency, $parentCode);
    }

    /**
     * @param $itemDataObjectFactory
     * @param $item
     * @param $priceIncludesTax
     * @param $useBaseCurrency
     * @param $parentCode
     * @return QuoteDetailsItemInterface
     */
    private function mapItem(
        $itemDataObjectFactory,
        $item,
        $priceIncludesTax,
        $useBaseCurrency,
        $parentCode
    ) {
        /** @var QuoteDetailsItemInterface $itemDataObject */
        $itemDataObject = $itemDataObjectFactory->create();
        $itemDataObject->setCode($item->getTaxCalculationItemId())
            ->setQuantity($item->getQty())
            ->setTaxClassKey(
                $this->taxClassKeyDataObjectFactory->create()
                    ->setType(TaxClassKeyInterface::TYPE_ID)
                    ->setValue($item->getProduct()->getTaxClassId())
            )
            ->setIsTaxIncluded($priceIncludesTax)
            ->setType(CommonTaxCollector::ITEM_TYPE_PRODUCT);

        if ($useBaseCurrency) {
            if (!$item->getBaseTaxCalculationPrice()) {
                $item->setBaseTaxCalculationPrice($item->getBaseCalculationPriceOriginal());
            }

            if ($this->taxHelper->applyTaxOnOriginalPrice()) {
                $baseTaxCalculationPrice = $item->getBaseOriginalPrice();
            } else {
                $baseTaxCalculationPrice = $item->getBaseCalculationPriceOriginal();
            }
            $this->setPriceForTaxCalculation($itemDataObject, (float)$baseTaxCalculationPrice);

            $itemDataObject->setUnitPrice($item->getBaseTaxCalculationPrice())
                ->setDiscountAmount($item->getBaseDiscountAmount());
        } else {
            if (!$item->getTaxCalculationPrice()) {
                $item->setTaxCalculationPrice($item->getCalculationPriceOriginal());
            }

            if ($this->taxHelper->applyTaxOnOriginalPrice()) {
                $taxCalculationPrice = $item->getOriginalPrice();
            } else {
                $taxCalculationPrice = $item->getCalculationPriceOriginal();
            }
            $this->setPriceForTaxCalculation($itemDataObject, (float)$taxCalculationPrice);

            $itemDataObject->setUnitPrice($item->getTaxCalculationPrice())
                ->setDiscountAmount($item->getDiscountAmount());
        }

        $itemDataObject->setParentCode($parentCode);

        return $itemDataObject;
    }

    /**
     * Set price for tax calculation.
     *
     * @param QuoteDetailsItemInterface $quoteDetailsItem
     * @param float $taxCalculationPrice
     * @return void
     */
    private function setPriceForTaxCalculation(QuoteDetailsItemInterface $quoteDetailsItem, float $taxCalculationPrice)
    {
        $extensionAttributes = $quoteDetailsItem->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->quoteDetailsItemExtensionFactory->create();
        }
        $extensionAttributes->setPriceForTaxCalculation($taxCalculationPrice);
        $quoteDetailsItem->setExtensionAttributes($extensionAttributes);
    }
}
