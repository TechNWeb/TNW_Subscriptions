<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductBillingFrequency;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote as ModelQuote;
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
     * DescriptionCreator constructor.
     *
     * @param Context $context
     * @param BillingFrequencyRepository $frequencyRepository
     * @param BillingFrequencyUnitType $frequencyUnitType
     * @param TrialLengthUnitType $trialLengthUnitType
     */
    public function __construct(
        Context $context,
        BillingFrequencyRepository $frequencyRepository,
        BillingFrequencyUnitType $frequencyUnitType,
        TrialLengthUnitType $trialLengthUnitType
    ) {
        $this->context = $context;
        $this->frequencyRepository = $frequencyRepository;
        $this->frequencyUnitType = $frequencyUnitType;
        $this->trialLengthUnitType = $trialLengthUnitType;
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
     *      Example: "$10.25 / every month(s). Total of 3 shipments. Products will be shipped every month(s) starting today".
     *
     * @param array $subscriptionData
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDescription(array $subscriptionData)
    {
        $isTrial = $subscriptionData[CreateProfile::UNIQUE]['is_trial'];
        $formattedPrice = ($subscriptionData[CreateProfile::NON_UNIQUE]['totalPrice'])
            ? $this->formatPrice($subscriptionData[CreateProfile::NON_UNIQUE]['totalPrice'])
            : __('Free');
        $frequencyUnit = $this->getFrequencyWithUnit($subscriptionData[CreateProfile::UNIQUE]['billing_frequency']);
        $subscriptionPeriod = $subscriptionData[CreateProfile::UNIQUE]['period'];

        $startDate = $this->formatStartDate($subscriptionData[CreateProfile::UNIQUE]['start_on']);

        if ($isTrial) {
            $frequencyTrialPeriod = $this->getFrequencyTrialWithUnit(
                $subscriptionData[CreateProfile::UNIQUE]['trial_period'],
                $subscriptionData[CreateProfile::UNIQUE]['trial_unit_id']);
            $description[] = __('%1 for %2 and then ', $formattedPrice, $frequencyTrialPeriod);
        } else if ($subscriptionData[CreateProfile::NON_UNIQUE]['initialFee']) {
            $description[] = __("%1 initial payment and then ", $formattedPrice);
        }

        $description[] = __('%1 / every %2. ',
            $this->formatPrice($subscriptionData[CreateProfile::NON_UNIQUE]['price']), $frequencyUnit);

        if (!$subscriptionData[CreateProfile::UNIQUE]['term']) {
            $description[] = __('Total of %1 %2. ',
                $subscriptionPeriod,
                $this->getShipmentLabel($subscriptionPeriod)
            );
        }

        $shipOrUse = !empty($subscriptionData[CreateProfile::NON_UNIQUE]['isVirtual'])
            ? __('can be used')
            : __('will be shipped');

        $description[] = __('Products %1 every %2 starting %3.', $shipOrUse, $frequencyUnit, $startDate);

        return implode(' ', $description);
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
        $formattedPrice = ($itemTotal + $initialFee)
            ? $this->formatPrice($itemTotal + $initialFee)
            : __('Free');
        $formattedPrice = $this->addContainer(
            $formattedPrice,
            'price'
        );

        if ($isTrial) {
            $middlePhrase = ($itemTotal + $initialFee) ? __('for the') : '';
            $lastPhrase = __('trial');
        } elseif ($initialFee) {
            $middlePhrase = ' ';
            $lastPhrase = __('initial payment');
        }

        if ($middlePhrase && $lastPhrase) {
            $result .= '<div class="subscription-price">';
            $result .= sprintf(
                '%s %s ',
                $formattedPrice,
                $this->addContainer($middlePhrase .' '. $lastPhrase, 'middle-text')
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
            '/' .$this->getFrequencyWithUnit($subscriptionData[CreateProfile::UNIQUE]['billing_frequency']),
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
     * @return float
     */
    private function formatPrice($price)
    {
        return $this->context->getPriceCurrency()->format(
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
