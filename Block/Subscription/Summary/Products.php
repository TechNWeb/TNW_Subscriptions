<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileAttributeInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context as FormContext;

/**
 * Class Products
 */
class Products extends BaseSummary
{
    /**
     * @var \TNW\Subscriptions\Model\BillingFrequencyRepository
     */
    private $frequencyRepository;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    private $currency;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * @var FormContext
     */
    private $formContext;

    /**
     * @var \TNW\Subscriptions\Model\ProductSubscriptionProfile\AttributeRepository
     */
    private $productAttributeRepository;

    /**
     * @var \TNW\Subscriptions\Model\ProductSubscriptionProfile\ManagerConfigurable
     */
    private $managerConfigurable;

    /**
     * @param Template\Context $context
     * @param \TNW\Subscriptions\Model\BillingFrequencyRepository $frequencyRepository
     * @param \Magento\Framework\Locale\CurrencyInterface $currency
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator
     * @param FormContext $formContext
     * @param \TNW\Subscriptions\Model\ProductSubscriptionProfile\AttributeRepository $productAttributeRepository
     * @param \TNW\Subscriptions\Model\ProductSubscriptionProfile\ManagerConfigurable $managerConfigurable
     */
    public function __construct(
        Template\Context $context,
        \TNW\Subscriptions\Model\BillingFrequencyRepository $frequencyRepository,
        \Magento\Framework\Locale\CurrencyInterface $currency,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator,
        FormContext $formContext,
        \TNW\Subscriptions\Model\ProductSubscriptionProfile\AttributeRepository $productAttributeRepository,
        \TNW\Subscriptions\Model\ProductSubscriptionProfile\ManagerConfigurable $managerConfigurable,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->frequencyRepository = $frequencyRepository;
        $this->currency = $currency;
        $this->orderRepository = $orderRepository;
        $this->descriptionCreator = $descriptionCreator;
        $this->formContext = $formContext;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->managerConfigurable = $managerConfigurable;
    }

    /**
     * @return ProductSubscriptionProfileInterface[]
     */
    public function getItems()
    {
        return $this->getSubscriptionProfile() ? $this->getSubscriptionProfile()->getVisibleProducts() : [];
    }

    /**
     * @return SubscriptionProfile[]
     */
    public function getSubscriptionProfiles()
    {
        $profiles = $this->getData('subscription_profile')
            ? [$this->getData('subscription_profile')]
            : null;
        return $this->getData('subscription_profiles') ?? $profiles;
    }

    /**
     * Retrieve product image
     *
     * @param ProductSubscriptionProfileInterface $item
     * @return string
     */
    public function getImageUrl(ProductSubscriptionProfileInterface $item)
    {
        $imageHelper = $this->formContext->getImageHelperForSubscriptionProduct($item, 'product_small_image');
        return ($imageHelper) ? $imageHelper->getUrl() : '';
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

        return $this->_localeDate->date(new \DateTime($startOn));
    }

    /**
     * Start on date formatted.
     *
     * @return string
     */
    public function getStartOnFormatted()
    {
        $date = $this->getStartOn();

        return $this->_localeDate->formatDate($date, \IntlDateFormatter::SHORT);
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
        $presetQty = (int)$item->getTnwSubscrUnlockPresetQty();

        return $presetQty
            ? $item->getPrice()
            : $item->getPrice() * $item->getQty();
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTerm()
    {
        $result = __('Until canceled');
        if (!$this->getSubscriptionProfile()->getTerm()) {
            $totalBillingCycles = $this->getSubscriptionProfile()->getTotalBillingCycles();
            $result = (int) $totalBillingCycles === 1
                ? __('Bill once')
                : __('Bill %1 times', $totalBillingCycles);
        }

        return $result;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFrequencyDescription()
    {
        $profile = $this->getSubscriptionProfile();
        if (!$profile) {
            return '';
        }
        $initialFee = $price = 0;
        foreach ($this->getItems() as $item) {
            $price += $this->getPrice($item);
            $initialFee += $item->getInitialFee();
        }

        $orderData = $this->getSubscriptionProfile()->getResource()
            ->getFirstOrderData($profile);

        return $this->descriptionCreator->getDescription([
            CreateProfile::UNIQUE => [
                'is_trial' => (bool)$profile->getTrialLength(),
                'billing_frequency' => $profile->getBillingFrequencyId(),
                'period' => $profile->getTotalBillingCycles(),
                'start_on' => $profile->getStartDate(),
                'trial_period' => $profile->getTrialLength(),
                'trial_unit_id' =>  $profile->getTrialLengthUnit(),
                'term' => $profile->getTerm(),
            ],
            CreateProfile::NON_UNIQUE => [
                'totalPrice' => isset($orderData['magento_order_id'])
                    ? $this->orderRepository->get($orderData['magento_order_id'])->getGrandTotal()
                    : 0,
                'initialFee' => $initialFee > 0,
                'price' => $price,
                'isVirtual' => $profile->getIsVirtual(),
            ]
        ]);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProfileFrequencyDescription()
    {
        $profile = $this->getSubscriptionProfile();
        if (!$profile) {
            return '';
        }

        return $this->descriptionCreator->getDescription([
            CreateProfile::UNIQUE => [
                'is_trial' => false,
                'billing_frequency' => $profile->getBillingFrequencyId(),
                'period' => $profile->getTotalBillingCycles(),
                'term' => $profile->getTerm(),
            ],
            CreateProfile::NON_UNIQUE => [
                'totalPrice' =>  $profile->getGrandTotal(),
                'initialFee' => false,
                'price' => $profile->getGrandTotal(),
                'isVirtual' => $profile->getIsVirtual(),
            ]
        ]);
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
        return $currency->toCurrency(sprintf('%f', $value));
    }

    /**
     * @param ProductSubscriptionProfileInterface $item
     * @return array
     */
    public function getItemOptions(ProductSubscriptionProfileInterface $item)
    {
        return $this->managerConfigurable->getItemOptions($item);
    }

    /**
     * @param $optionValue
     * @return array
     */
    public function getFormatedOptionValue($optionValue)
    {
        return $this->managerConfigurable->getFormatedOptionValue($optionValue);
    }

    /**
     * get visible attributes
     * @param ProductSubscriptionProfileInterface $item
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute[]
     */
    public function customAttributes(ProductSubscriptionProfileInterface $item)
    {
        $attributes = array_map(function (AttributeInterface $attribute) {
            return $this->productAttributeRepository->get($attribute->getAttributeCode());
        }, $item->getCustomAttributes());

        $attributes = array_filter($attributes, function (ProductSubscriptionProfileAttributeInterface $attribute) {
            return $attribute->getIsVisibleOnFront();
        });

        return $attributes;
    }
}
