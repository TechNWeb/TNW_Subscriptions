<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */


namespace TNW\Subscriptions\Model\Checkout\ConfigProvider;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use TNW\Subscriptions\Model\BillingFrequencyRepository;
use TNW\Subscriptions\Model\Checkout\ConfigProviderInterface;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Provide subscriptions plans config for component on storefront subscriptions cart view.
 * @todo refactor this total shit into some acceptable class.
 */
class SubscriptionPlans implements ConfigProviderInterface
{
    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @param DataObjectProcessor $dataObjectProcessor
     */
    public function __construct(
        DataObjectProcessor $dataObjectProcessor
    ) {
        $this->dataObjectProcessor = $dataObjectProcessor;

    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'subscriptionPlans' => $this->getSubQuotesData(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    private function getSubQuotesData()
    {
        $data = [];
        $index = 0;
        foreach ($this->getObjects() as $subQuote) {
            $quote = [];
            $index++;
            $quote['quote_id'] = $subQuote->getId();
            $quote['plan_title'] = 'Subscription plan #' . $index;
            /** @var Item $cartItem */
            foreach ($this->getObjectItems($subQuote) as $cartItem) {
                $product = $this->getProductRepository()->getById($this->getProductFromItem($cartItem)->getId());
                $subBuyRequest = $cartItem->getBuyRequest()->getDataByPath(Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);
                $item = array_merge(
                    $subBuyRequest[Create::UNIQUE],
                    $subBuyRequest[Create::NON_UNIQUE]
                );
                $item['quote_id'] = $subQuote->getId();
                $item['trial_period'] = $this->getTrialPeriod($product->getId());
                $item['name'] = $product->getName();
                $item['description'] = $this->getDescription($product);
                $item['qty'] = $cartItem->getQty();
                /** @var \Magento\Catalog\Helper\Image $helper */
                $helper = $this->getImageHelperFactory()->create()
                    ->init($product, 'category_page_grid');
                $item['image'] = $helper->getUrl();
                $item['price'] = $this->getPrice($subQuote, $subBuyRequest);
                $item['billing_frequencies'] = $this->getBillingFrequency($product->getId(), $item['billing_frequency']);
                $quote['products'][] = $item;
            }
            $data[] = $quote;
        }

        return $data;
    }

    /**
     * Returns list of objects to display.
     *
     * @return Quote[]
     */
    protected function getObjects()
    {
        return ObjectManager::getInstance()->get(QuoteSessionInterface::class)->getSubQuotes();
    }

    /**
     * Returns list of object items to display.
     *
     * @param Quote $object
     * @return Item[]
     */
    protected function getObjectItems(Quote $object)
    {
        return $object->getAllItems();
    }

    /**
     * @return ProductRepositoryInterface
     */
    private function getProductRepository()
    {
        return ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
    }

    /**
     * Returns product from item.
     *
     * @param Item $item
     * @return mixed
     */
    protected function getProductFromItem(Item $item)
    {
        return $item->getProduct();
    }

    /**
     * Get trial period as string for product.
     *
     * @param null|int $productId
     * @return \Magento\Framework\Phrase|string
     */
    protected function getTrialPeriod($productId)
    {
        $trialLength = 0;
        $trialUnit = 0;
        $trialPriceLabel = '';
        $product = $this->getProductRepository()->getById($productId);
        $show = (bool)$product->getCustomAttribute(
            Attribute::SUBSCRIPTION_TRIAL_STATUS
        )->getValue();

        if ($show) {
            $trialLength = $product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH)
                ? (int)$product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH)->getValue()
                : 0;
            $trialUnit = $product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT)
                ? (int)$product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT)->getValue()
                : 0;
            $trialUnit = $this->getUnitType()->getLabelByValueAndLength($trialUnit, $trialLength);

            $currencySymbol = $this->getCurrentCurrencySymbol();
            $trialPriceLabel = (int)$product->getCustomAttribute(
                    Attribute::SUBSCRIPTION_TRIAL_PRICE
                )->getValue() . $currencySymbol . ' ' . __('for') . ' ';
        }

        return $trialLength && $trialUnit ? $trialPriceLabel . $trialLength . ' ' . $trialUnit : '';
    }

    public function getPrice(Quote $quote, array $subscriptionData)
    {
        $isTrial = $subscriptionData[CreateProfile::UNIQUE]['is_trial'];
        $formattedPrice = $this->formatPrice($quote->getBaseGrandTotal());
        $frequencyUnit = $this->getFrequencyWithUnit($subscriptionData[CreateProfile::UNIQUE]['billing_frequency']);
        $subscriptionPeriod = $subscriptionData[CreateProfile::UNIQUE]['period'];

        $startDate = $this->formatStartDate($subscriptionData[CreateProfile::UNIQUE]['start_on']);
        $trialPart = '';
        $noTrialPart = '';

        if ($isTrial) {
            $trialTotal = $formattedPrice;
            $frequencyTrialPeriod = $this->getFrequencyTrialWithUnit(
                $subscriptionData[CreateProfile::UNIQUE]['trial_period'],
                $subscriptionData[CreateProfile::UNIQUE]['trial_unit_id']);
            $trialPart = sprintf(__('%s for %s and then '), $trialTotal, $frequencyTrialPeriod);
        } else {
            if ($subscriptionData[CreateProfile::NON_UNIQUE]['initial_fee']) {
                $noTrialPart = sprintf("%s initial charge and then ", $formattedPrice);
            }
        }

        $total = $this->formatPrice($subscriptionData[CreateProfile::NON_UNIQUE]['price']);
        $priceWithUnit = sprintf('<span class="price">%s </span>/ %s %s. ', $total, __('every'), $frequencyUnit);

        return $trialPart . $noTrialPart . $priceWithUnit;
    }

    /**
     * Get currency symbol from session if exists here or from store manager.
     *
     * @return string
     */
    protected function getCurrentCurrencySymbol()
    {
        $currencyCode = $this->getQuoteSession()->getCurrencyId();

        if ($currencyCode) {
            /** @var \Magento\Directory\Model\Currency $currency */
            $currency = $this->getCurrencyFactory()->create()->load($currencyCode);
            $currencySymbol = $currency->getCurrencySymbol();
        } else {
            $currencySymbol = $this->getStoreManager()->getStore()->getBaseCurrency()->getCurrencySymbol();
        }

        return $currencySymbol;
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
        $unitLabel = $this->getTrialLengthUnitType()->getLabelByValueAndLength($unitId, $period);

        return strtolower($period . ' ' . $unitLabel);
    }

    /**
     * Return Billing Frequency with unit (e.g. "6 months")
     *
     * @param string|int $billingFrequencyId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getFrequencyWithUnit($billingFrequencyId)
    {
        $billingFrequency = $this->getBillingFrequencyRepository()->getById(
            $billingFrequencyId
        );

        $frequency = $billingFrequency->getFrequency();

        $label = $this->getBillingFrequencyUnitType()->getLabelByValueAndFrequency(
            $billingFrequency->getUnit(),
            $frequency
        );

        if ($frequency > 1) {
            $label = $frequency . ' ' . $label;
        }

        return strtolower($label);
    }

    /**
     * Return formatted Start Date
     *
     * @param $startDate
     * @return \Magento\Framework\Phrase|string
     */
    private function formatStartDate($startDate)
    {
        $nowDate = new \DateTime();
        $nowDate = $nowDate->format('Y-m-d');
        if ($startDate === $nowDate) {
            $startDate = __('today');
        } else {
            $startDate = $this->getTimeZone()->formatDate(
                $startDate,
                \IntlDateFormatter::LONG
            );
        }

        return $startDate;
    }

    /**
     * Return formatted price
     *
     * @param $price
     * @return float
     */
    private function formatPrice($price)
    {
        return $this->getPriceCurrency()->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION
        );
    }

    /**
     * @return TrialLengthUnitType
     */
    private function getUnitType()
    {
        return ObjectManager::getInstance()->get(TrialLengthUnitType::class);
    }

    /**
     * @return QuoteSessionInterface
     */
    private function getQuoteSession()
    {
        return ObjectManager::getInstance()->get(QuoteSessionInterface::class);
    }

    /**
     * @return CurrencyFactory
     */
    private function getCurrencyFactory()
    {
        return ObjectManager::getInstance()->get(CurrencyFactory::class);
    }

    /**
     * @return StoreManagerInterface
     */
    private function getStoreManager()
    {
        return ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * @return PriceCurrencyInterface
     */
    private function getPriceCurrency()
    {
        return ObjectManager::getInstance()->get(PriceCurrencyInterface::class);
    }

    /**
     * @return TrialLengthUnitType
     */
    private function getTrialLengthUnitType()
    {
        return ObjectManager::getInstance()->get(TrialLengthUnitType::class);
    }

    /**
     * @return BillingFrequencyRepository
     */
    private function getBillingFrequencyRepository()
    {
        return ObjectManager::getInstance()->get(BillingFrequencyRepository::class);
    }

    /**
     * @return BillingFrequencyUnitType
     */
    private function getBillingFrequencyUnitType()
    {
        return ObjectManager::getInstance()->get(BillingFrequencyUnitType::class);
    }

    /**
     * @return TimezoneInterface
     */
    private function getTimeZone()
    {
        return ObjectManager::getInstance()->get(TimezoneInterface::class);
    }

    /**
     * @return \Magento\Catalog\Helper\ImageFactory
     */
    private function getImageHelperFactory()
    {
        return ObjectManager::getInstance()->get(\Magento\Catalog\Helper\ImageFactory::class);
    }

    /**
     * @param Product $product
     * @return string
     */
    private function getDescription($product)
    {
        $maxLength = 340;
        $description = $product->getData('short_description');

        if (strlen($description) > $maxLength) {
            $offset = ($maxLength - 7) - strlen($description);
            $description = substr($description, 0, strrpos($description, ' ', $offset)) . '...</p>';
        }

        return $description;
    }

    private function getBillingFrequency($productId, $checkedId)
    {
        $result = [];
        try {
            $product = $this->getProductRepository()->getById($productId);
            $productPrice = (int)$product->getPrice();
            /** @var ProductBillingFrequencyInterface $productFrequency */
            foreach ($this->getProductBillingFrequencies($productId) as $productFrequency) {
                $frequency = $this->getBillingFrequencyRepository()->getById($productFrequency->getBillingFrequencyId());
                $label = $frequency->getLabel();
                if ($productId) {
                    $frequencyPrice = (int)$productFrequency->getPrice();
                    if ($productPrice > $frequencyPrice) {
                        $savings = $this->formatPrice($productPrice - $frequencyPrice);
                        $label .= '  ' . sprintf(__('(SAVE %s)'), $savings);
                    }

                }
                $checked = (int)$checkedId === (int)$productFrequency->getBillingFrequencyId()
                    ? $productFrequency->getBillingFrequencyId() : '0';
                $result[] = [
                    'label' => $label,
                    'value' => $productFrequency->getBillingFrequencyId(),
                    'checked' => $checked,
                ];

            }
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
        }

        return $result;
    }

    /**
     * Returns list of product billing frequencies.
     *
     * @param null|int $productId
     * @return array|ProductBillingFrequencyInterface[]
     */
    private function getProductBillingFrequencies($productId)
    {
        try {
            $productBillingFrequencies = $this->getReccuringOptionRepository()
                ->getListByProductId($productId)
                ->getItems();
        } catch (\Exception $e) {
            $productBillingFrequencies = [];
            $this->getLogger()->error($e->getMessage());
        }

        return $productBillingFrequencies;
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger()
    {
        return ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * @return ProductBillingFrequencyRepositoryInterface
     */
    private function getReccuringOptionRepository()
    {
        return ObjectManager::getInstance()->get(ProductBillingFrequencyRepositoryInterface::class);
    }
}
