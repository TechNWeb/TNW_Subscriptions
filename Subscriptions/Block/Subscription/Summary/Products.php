<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * @method \TNW\Subscriptions\Model\SubscriptionProfile getSubscriptionProfile()
 */
class Products extends Template
{
    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    private $imageFactory;

    /**
     * @var \TNW\Subscriptions\Model\BillingFrequencyRepository
     */
    private $frequencyRepository;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    private $currency;

    /**
     * @var \TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType
     */
    private $frequencyUnitType;

    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile
     */
    private $resourceSubscriptionProfile;

    public function __construct(
        Template\Context $context,
        \Magento\Catalog\Helper\ImageFactory $imageFactory,
        \TNW\Subscriptions\Model\BillingFrequencyRepository $frequencyRepository,
        \Magento\Framework\Locale\CurrencyInterface $currency,
        \TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType $frequencyUnitType,
        \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile $resourceSubscriptionProfile,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->imageFactory = $imageFactory;
        $this->frequencyRepository = $frequencyRepository;
        $this->currency = $currency;
        $this->frequencyUnitType = $frequencyUnitType;
        $this->resourceSubscriptionProfile = $resourceSubscriptionProfile;
    }

    /**
     * @return ProductSubscriptionProfileInterface[]
     */
    public function getItems()
    {
        return $this->getSubscriptionProfile()->getProducts();
    }

    /**
     * Retrieve product image
     *
     * @param ProductSubscriptionProfileInterface $item
     * @return string
     */
    public function getImageUrl($item)
    {
        $product = $this->getProductFromItem($item);
        if (null === $product) {
            return '';
        }

        return $this->imageFactory->create()
            ->init($product, 'product_small_image')
            ->getUrl();
    }

    /**
     * @param ProductSubscriptionProfileInterface $item
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProductFromItem(ProductSubscriptionProfileInterface $item)
    {
        try {
            return $item->getMagentoProduct();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * @param ProductSubscriptionProfileInterface $item
     * @return string
     */
    public function getName($item)
    {
        $product = $this->getProductFromItem($item);
        return null === $product
            ? $item->getName()
            : $product->getName();
    }

    /**
     * @param ProductSubscriptionProfileInterface $item
     * @return string
     */
    public function getDescription($item)
    {
        $product = $this->getProductFromItem($item);
        return null === $product
            ? __('Product deleted')
            : $product->getData('short_description');
    }

    /**
     * @return \DateTime
     */
    public function getStartOn()
    {
        $trialStartDate = $this->getSubscriptionProfile()->getTrialStartDate();
        $startOn = !empty($trialStartDate)
            ? $trialStartDate
            : $this->getSubscriptionProfile()->getStartDate();

        return new \DateTime($startOn);
    }

    /**
     * @return \Magento\Framework\Phrase|null|string
     */
    public function getBillingFrequencyLabel()
    {
        try {
            return $this->frequencyRepository
                ->getById($this->getSubscriptionProfile()->getBillingFrequencyId())
                ->getLabel();
        } catch (NoSuchEntityException $e) {
            return __('Product was deleted');
        }
    }

    /**
     * @param ProductSubscriptionProfileInterface $item
     * @return mixed
     */
    public function getPrice(ProductSubscriptionProfileInterface $item)
    {
        $product = $this->getProductFromItem($item);
        $presetQty = null === $product
            ? (int)$item->getTnwSubscrUnlockPresetQty()
            : (int)$product->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);

        return $presetQty
            ? $item->getPrice() * $item->getQty()
            : $item->getPrice();
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTerm()
    {
        $result = '';
        $term = $this->getSubscriptionProfile()->getTerm();
        $lastOrderData = $this->resourceSubscriptionProfile
            ->getLastOrderData($this->getSubscriptionProfile(), false);

        switch ($term) {
            case 0:
                $date = '';
                if (!empty($lastOrderData)) {
                    $date = $lastOrderData['scheduled_at'];
                }

                $this->getSubscriptionProfile()->getTotalBillingCycles();
                $result = $this->_localeDate->date($date)->format('F dS, Y');
                break;
            case 1:
                if (!empty($lastOrderData)) {
                    $result = __('Until canceled');
                }
                break;
            default:
                break;
        }

        return $result;
    }

    /**
     * @param ProductSubscriptionProfileInterface $item
     * @return string
     */
    public function getFrequencyDescription(ProductSubscriptionProfileInterface $item)
    {
        $frequencyUnit = $this->getFrequencyWithUnit($this->getSubscriptionProfile()->getBillingFrequencyId());
        return __('<b>%1/%2</b>. Shipped every %2 for %3 starting on %4',
            $this->formatPrice($this->getPrice($item)),
            $frequencyUnit,
            '2 years',
            $this->getStartOn()->format('F jS Y'));
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
     * Return formatted price
     *
     * @param string $value
     * @return string
     */
    public function formatPrice($value)
    {
        $currency = $this->currency->getCurrency($this->getSubscriptionProfile()->getProfileCurrencyCode());
        return $currency->toCurrency(sprintf("%f", $value));
    }
}