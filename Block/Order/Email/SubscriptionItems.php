<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Order\Email;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Helper\Image;
use TNW\Subscriptions\Model\BillingFrequencyRepository;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;

/**
 * Class SubscriptionItems - block used for subscription items in order email
 */
class SubscriptionItems extends Template
{
    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var BillingFrequencyRepository
     */
    protected $billingFrequencyRepository;

    /**
     * SubscriptionItems constructor.
     * @param Template\Context $context
     * @param Image $imageHelper
     * @param BillingFrequencyRepository $billingFrequencyRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Image $imageHelper,
        BillingFrequencyRepository $billingFrequencyRepository,
        array $data = []
    ) {
        $this->imageHelper = $imageHelper;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        parent::__construct($context, $data);
    }

    /**
     * Get product image
     *
     * @param $product
     * @return string
     */
    public function getImageThumbnail($product)
    {
        return $this->imageHelper->init($product, 'product_base_image')->getUrl();
    }

    /**
     * Get billing frequency label
     *
     * @param $billingFrequency
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBillingFrequencyLabel($billingFrequency)
    {
        return $this->billingFrequencyRepository->getById($billingFrequency)->getLabel();
    }

    /**
     * Get term
     *
     * @param $options
     * @return \Magento\Framework\Phrase
     */
    public function getTerm($options)
    {
        if ($options['term']) {
            $label = __('Until canceled');
        } else {
            $label = __('Bill %1 times', $options['period']);
        }
        return $label;
    }

    /**
     * Get next payment
     *
     * @param $options
     * @return string|null
     * @throws \Exception
     */
    public function getNextPayment($options)
    {
        return $this->calculateNextDate($options);
    }

    /**
     * Calculate next date payment
     *
     * @return string|null
     * @throws \Exception
     */
    private function calculateNextDate($options)
    {
        $result = null;

        if ($options['subscription_data']['unique']['start_on']) {
            $startDate = new \DateTime($options['subscription_data']['unique']['start_on']);

            $billingFrequency = $this->billingFrequencyRepository->getById($options['billing_frequency'])->getData();
            switch ($billingFrequency['unit']) {
                case TrialLengthUnitType::DAYS:
                    $intervalUnit = 'D';
                    break;
                case TrialLengthUnitType::MONTHS:
                    $intervalUnit = 'M';
                    break;
                default:
                    throw new LocalizedException(__('Undefined trial length unit type.'));
            }

            $expression = 'P' . $billingFrequency['frequency'] . $intervalUnit;
            $result = $startDate->add(new \DateInterval($expression))
                ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        }

        return $result;
    }
}
