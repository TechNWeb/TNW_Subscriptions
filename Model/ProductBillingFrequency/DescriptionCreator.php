<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ProductBillingFrequency;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\SerializerInterface;
use TNW\Subscriptions\Model\BillingFrequencyRepository;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Create description for billing frequency.
 */
class DescriptionCreator
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var BillingFrequencyRepository
     */
    private $frequencyRepository;
    /**
     * @var BillingFrequencyUnitType
     */
    private $frequencyUnitType;
    /**
     * @var TrialLengthUnitType
     */
    private $trialLengthUnitType;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * DescriptionCreator constructor.
     * @param Context $context
     * @param BillingFrequencyRepository $frequencyRepository
     * @param BillingFrequencyUnitType $frequencyUnitType
     * @param TrialLengthUnitType $trialLengthUnitType
     * @param SerializerInterface $serializer
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        Context $context,
        BillingFrequencyRepository $frequencyRepository,
        BillingFrequencyUnitType $frequencyUnitType,
        TrialLengthUnitType $trialLengthUnitType,
        SerializerInterface $serializer,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->context = $context;
        $this->frequencyRepository = $frequencyRepository;
        $this->frequencyUnitType = $frequencyUnitType;
        $this->trialLengthUnitType = $trialLengthUnitType;
        $this->serializer = $serializer;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Create description for billing frequency
     *
     * Used variables:
     *  [trial total] : calculates like SUM ( (product_trial_price + product_initial_fee) * qty).
     *      If [trial total] = 0 then [trial total] = 'Free';
     *      Example:"Free for 6 day(s) and then ...".
     *  [total]: calculates like SUM ( (product_frequency_price) * qty).
     *  [Billing frequency Unit]: its a field from Billing Frequency.
     *  [subscription period]: field from "Add to Subscription" form.
     *  [subscription start date]: start date of subscription
     *
     * Create description for billing frequency based on conditions:
     * If product trial is "On":
     *      "[trial total] for [Billing frequency trial period] and then [total] / every [Billing frequency Unit].
     *      Total of [subscription period] shipments. Products will be shipped every [Billing frequency Unit]
     *      starting [subscription start date]".
     *
     *      Example:" $5.99 for 6 day(s) and then $10.25 / every month(s).
     *          Total of 3 shipments. Products will be shipped every month(s) starting today".
     *
     * If product trial is "Off":
     *      "[total] / [Billing frequency Unit]. Total of [subscription period] shipments.
     *      Products will be shipped every [Billing frequency Unit] starting [subscription start date]".
     *
     * Example: "$10.25 / every month(s). Total of 3 shipments. Products will be shipped every month(s) starting today".
     *
     * @param array $subscriptionData
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDescription(array $subscriptionData)
    {
        $isTrial = $subscriptionData[CreateProfile::UNIQUE]['is_trial'];
        $storeId = array_key_exists(
            'storeId',
            $subscriptionData[CreateProfile::NON_UNIQUE]
        )
            ? $subscriptionData[CreateProfile::NON_UNIQUE]['storeId']
            : null;
        $formattedTotalPrice = ($subscriptionData[CreateProfile::NON_UNIQUE]['totalPrice'])
                ? $this->formatPrice(
                    $subscriptionData[CreateProfile::NON_UNIQUE]['totalPrice'],
                    $storeId
                )
                : __('Free');
        $formattedPrice = $this->formatPrice(
            $subscriptionData[CreateProfile::NON_UNIQUE]['price'],
            $storeId
        );
        $frequencyUnit = isset($subscriptionData[CreateProfile::UNIQUE]['billing_frequency']) ?
            $this->getFrequencyWithUnit($subscriptionData[CreateProfile::UNIQUE]['billing_frequency']) : false;
        $startDate = isset($subscriptionData[CreateProfile::UNIQUE]['start_on']) ?
            $this->formatStartDate($subscriptionData[CreateProfile::UNIQUE]['start_on']) : false;

        $subscriptionPeriod = $subscriptionData[CreateProfile::UNIQUE]['period'];

        if ($isTrial) {
            $frequencyTrialPeriod = $this->getFrequencyTrialWithUnit(
                $subscriptionData[CreateProfile::UNIQUE]['trial_period'],
                $subscriptionData[CreateProfile::UNIQUE]['trial_unit_id']
            );
            $description[] = __('%1 for %2 and then ', $formattedTotalPrice, $frequencyTrialPeriod);
        } elseif ($subscriptionData[CreateProfile::NON_UNIQUE]['initialFee']) {
            $description[] = __('%1 initial payment and then ', $formattedTotalPrice);
        }

        $description[] = __('%1 / every %2. ', $formattedPrice, $frequencyUnit);

        if (!$subscriptionData[CreateProfile::UNIQUE]['term']) {
            $description[] = __(
                'Total of %1 %2. ',
                $subscriptionPeriod,
                $this->getShipmentLabel($subscriptionPeriod)
            );
        }

        $shipOrUse = !empty($subscriptionData[CreateProfile::NON_UNIQUE]['isVirtual'])
            ? __('can be used')
            : __('will be shipped');

        if ($frequencyUnit && $startDate) {
            $description[] = __('Products %1 every %2 starting %3.', $shipOrUse, $frequencyUnit, $startDate);
        }

        return implode(' ', $description);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $groupItems
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDescriptionByGroup($groupItems)
    {
        $fullSubscriptionData = $this->fullSubscriptionData($groupItems);
        return $this->getDescription($fullSubscriptionData);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $groupItems
     *
     * @return string
     */
    public function getDescribedPriceHtmlByGroup($groupItems)
    {
        $fullSubscriptionData = $this->fullSubscriptionData($groupItems);
        return $this->getDescribedItemPriceHtml(
            $fullSubscriptionData['non_unique']['price'],
            $fullSubscriptionData,
            $fullSubscriptionData['non_unique']['initialPrice']
        );
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $groupItems
     *
     * @return array
     */
    private function fullSubscriptionData($groupItems)
    {
        $fullSubscriptionData = null;
        $isTrial = false;
        $isVirtual = true;
        $initialFee = $subtotal = $initialSubtotal = 0;
        foreach ($groupItems as $item) {
            if (!$fullSubscriptionData) {
                $fullSubscriptionData[CreateProfile::UNIQUE] =
                    $this->serializer->unserialize($item->getOptionByCode('subscription')->getValue());
                $isTrial = $fullSubscriptionData[CreateProfile::UNIQUE]['is_trial'];
            }

            if ($isTrial) {
                $infoBuyRequestData = $this->serializer->unserialize($item->getOptionByCode('info_buyRequest')
                    ->getValue());
                $infoBuyRequestData = $infoBuyRequestData[CreateProfile::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME];
                $price = $infoBuyRequestData[CreateProfile::NON_UNIQUE]['price'];
                if (is_array($price)) {
                    $price = $price[0];
                }
                $subtotal += $price * $item->getQty();
                $initialSubtotal += $item->getRowTotal();
            } else {
                $subtotal += $item->getRowTotal();
            }
            $initialFee += $this->getInitialFeeFromItem($item);

            if (!$item->isDeleted() && !$item->getParentItemId() && !$item->getProduct()->getIsVirtual()) {
                $isVirtual = false;
            }
        }

        $fullSubscriptionData[CreateProfile::NON_UNIQUE] = [
            'price' => $subtotal,
            'totalPrice' => ($isTrial ? $initialSubtotal : $subtotal) + $initialFee,
            'initialPrice' => $initialFee,
            'initialFee' => $initialFee > 0,
            'isVirtual' => $isVirtual,
        ];

        return $fullSubscriptionData;
    }

    /**
     * Returns initial fee from item.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return float
     */
    private function getInitialFeeFromItem($item)
    {
        $extensionAttributes = $item->getExtensionAttributes();
        if (!$extensionAttributes instanceof \Magento\Quote\Api\Data\CartItemExtensionInterface) {
            return 0;
        }

        $subsInitialFees = $extensionAttributes->getSubsInitialFees();
        if (!$subsInitialFees instanceof \TNW\Subscriptions\Model\Sales\ExtensionAttributes\QuoteItem) {
            return 0;
        }
        $qty = $item->getBuyRequest()->getUsePresetQty() ? 1 : $item->getQty();

        return $subsInitialFees->getSubsInitialFee() * $qty;
    }

    /**
     * Returns item price with description.
     *
     * @param float|string $itemTotal
     * @param array $subscriptionData
     * @param float|null $initialFee
     * @return string
     */
    public function getDescribedItemPriceHtml($itemTotal, array $subscriptionData, $initialFee = null)
    {
        $result = '';
        $middlePhrase = '';
        $lastPhrase = '';
        $thenPhrase = '';
        $priceClasses = ['base-price'];
        $isTrial = $subscriptionData[CreateProfile::UNIQUE]['is_trial'];
        $initialItemTotal = $subscriptionData[CreateProfile::NON_UNIQUE]['totalPrice'];
        $formattedPrice = $initialItemTotal > 0
            ? $this->formatPrice($initialItemTotal)
            : __('Free');
        $formattedPrice = $this->addContainer(
            $formattedPrice,
            'price'
        );

        if ($isTrial) {
            $middlePhrase = ($itemTotal + $initialFee) ? __('for') : '';
            $lastPhrase = $this->getFrequencyTrialWithUnit(
                $subscriptionData[CreateProfile::UNIQUE]['trial_period'],
                $subscriptionData[CreateProfile::UNIQUE]['trial_unit_id']
            );
        } elseif ($initialFee) {
            $middlePhrase = ' ';
            $lastPhrase = __('initial payment');
        }

        if ($middlePhrase && $lastPhrase) {
            $result .= '<div class="subscription-price">';
            $result .= sprintf(
                '%s %s ',
                $formattedPrice,
                $this->addContainer($middlePhrase . ' ' . $lastPhrase, 'middle-text')
            );
            $result .= '</div>';
            $thenPhrase = __('then');
            $priceClasses[] = 'has-trial';
        }

        $total = $this->addContainer(
            $this->formatPrice($itemTotal),
            'price'
        );

        $frequencyUnit = $this->addContainer(
            '/' . $this->getFrequencyWithUnit($subscriptionData[CreateProfile::UNIQUE]['billing_frequency']),
            'unit'
        );

        return sprintf(
            '%s<div class="%s">%s %s</div>',
            $result,
            implode(' ', $priceClasses),
            $thenPhrase,
            $total . $frequencyUnit
        );
    }

    /**
     * @param $quoteItem
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDescribedItemPriceHtmlByQuoteItem($quoteItem)
    {
        $subscriptionData = $this->fullSubscriptionData([$quoteItem]);

        return $this->getDescription($subscriptionData);
    }

    /**
     * Return Billing Frequency with unit (e.g. "6 months")
     *
     * @param string|int $billingFrequencyId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFrequencyWithUnit($billingFrequencyId)
    {
        $billingFrequency = $this->frequencyRepository->getById(
            $billingFrequencyId
        );

        $frequency = $billingFrequency->getFrequency();

        $label = $this->frequencyUnitType->getLabelByValueAndFrequency(
            $billingFrequency->getUnit(),
            $frequency
        );

        if ($frequency > 1) {
            $label = $frequency . ' ' . $label;
        }

        return strtolower($label);
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
        $unitLabel = $this->trialLengthUnitType->getLabelByValueAndLength($unitId, $period);

        return strtolower($period . ' ' . $unitLabel);
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
            $startDate = $this->context->getLocaleDate()->formatDate(
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
     * @param null $storeId
     * @return float
     */
    public function formatPrice($price, $storeId = null)
    {
        if ($storeId) {
            $storeCurrency = $this->priceCurrency->getCurrency($storeId);
        } else {
            $storeCurrency = $this->context->getPriceCurrency();
        }
        return $storeCurrency->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION
        );
    }

    /**
     * Get shipment label depends on subscription period.
     *
     * @param int $subscriptionPeriod
     * @return \Magento\Framework\Phrase
     */
    private function getShipmentLabel($subscriptionPeriod)
    {
        $label = 'shipment';

        if ($subscriptionPeriod != 1) {
            $label .= "s";
        }

        return __($label);
    }

    /**
     * Adds span container to text.
     *
     * @param string $text
     * @param $class
     * @return string
     */
    private function addContainer($text, $class)
    {
        return '<span class="' . $class . '">' . $text . '</span>';
    }
}
