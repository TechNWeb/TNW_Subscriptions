<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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
     * @param ModelQuote $quote
     * @param array $subscriptionData
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDescription(ModelQuote $quote, array $subscriptionData)
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
        $priceWithUnit = sprintf('%s / %s %s. ', $total, __('every'), $frequencyUnit);

        $shipmentLabel = $this->getShipmentLabel($subscriptionPeriod);
        $shippingInformation = sprintf(
            __('Total of %s %s. Products will be shipped every %s starting %s.'),
            $subscriptionPeriod,
            $shipmentLabel,
            $frequencyUnit,
            $startDate
        );

        return $trialPart . $noTrialPart . $priceWithUnit . $shippingInformation;
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


}
