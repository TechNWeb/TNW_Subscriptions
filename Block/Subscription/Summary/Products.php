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
     * @var \Magento\Catalog\Model\Product\OptionFactory
     */
    private $productOptionFactory;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    private $stringUtils;

    /**
     * @param Template\Context $context
     * @param \TNW\Subscriptions\Model\BillingFrequencyRepository $frequencyRepository
     * @param \Magento\Framework\Locale\CurrencyInterface $currency
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator
     * @param FormContext $formContext
     * @param \TNW\Subscriptions\Model\ProductSubscriptionProfile\AttributeRepository $productAttributeRepository
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param \Magento\Framework\Stdlib\StringUtils $stringUtils
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \TNW\Subscriptions\Model\BillingFrequencyRepository $frequencyRepository,
        \Magento\Framework\Locale\CurrencyInterface $currency,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator,
        FormContext $formContext,
        \TNW\Subscriptions\Model\ProductSubscriptionProfile\AttributeRepository $productAttributeRepository,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Framework\Stdlib\StringUtils $stringUtils,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->frequencyRepository = $frequencyRepository;
        $this->currency = $currency;
        $this->orderRepository = $orderRepository;
        $this->descriptionCreator = $descriptionCreator;
        $this->formContext = $formContext;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productOptionFactory = $productOptionFactory;
        $this->stringUtils = $stringUtils;
    }

    /**
     * @return ProductSubscriptionProfileInterface[]
     */
    public function getItems()
    {
        return $this->getSubscriptionProfile()->getVisibleProducts();
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
     */
    public function getFrequencyDescription()
    {
        $profile = $this->getSubscriptionProfile();

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
        $result = [];
        $options = $item->getCustomOptions();
        if ($options) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }

            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }

            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }
        }

        return $result;
    }

    public function getFormatedOptionValue($optionValue)
    {
        $optionInfo = [];

        // define input data format
        if (is_array($optionValue)) {
            if (isset($optionValue['option_id'])) {
                $optionInfo = $optionValue;
                if (isset($optionInfo['value'])) {
                    $optionValue = $optionInfo['value'];
                }
            } elseif (isset($optionValue['value'])) {
                $optionValue = $optionValue['value'];
            }
        }

        // render customized option view
        if (isset($optionInfo['custom_view']) && $optionInfo['custom_view']) {
            $default = ['value' => $optionValue];
            if (isset($optionInfo['option_type'])) {
                try {
                    $group = $this->productOptionFactory->create()->groupFactory($optionInfo['option_type']);
                    return ['value' => $group->getCustomizedView($optionInfo)];
                } catch (\Exception $e) {
                    return $default;
                }
            }
            return $default;
        }

        // truncate standard view
        if (is_array($optionValue)) {
            $truncatedValue = implode("\n", $optionValue);
            $truncatedValue = nl2br($truncatedValue);
            return ['value' => $truncatedValue];
        }

        $truncatedValue = $this->filterManager->truncate($optionValue, ['length' => 55, 'etc' => '']);
        $truncatedValue = nl2br($truncatedValue);

        $result = ['value' => $truncatedValue];
        if ($this->stringUtils->strlen($optionValue) > 55) {
            $result['value'] .= ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>';
            $optionValue = nl2br($optionValue);
            $result = array_merge($result, ['full_view' => $optionValue]);
        }

        return $result;
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
