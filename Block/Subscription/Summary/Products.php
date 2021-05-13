<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Sales\Model\OrderRepository;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileAttributeInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\BillingFrequencyRepository;
use TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\AttributeRepository;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ManagerConfigurable;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context as FormContext;

/**
 * Class Products - subscription product summary block
 */
class Products extends BaseSummary
{
    /**
     * @var BillingFrequencyRepository
     */
    private $frequencyRepository;

    /**
     * @var CurrencyInterface
     */
    private $currency;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * @var FormContext
     */
    private $formContext;

    /**
     * @var AttributeRepository
     */
    private $productAttributeRepository;

    /**
     * @var ManagerConfigurable
     */
    private $managerConfigurable;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var AreProductsSalableForRequestedQtyInterface
     */
    private $productsSalableForRequestedQty;

    /**
     * @var IsProductSalableForRequestedQtyRequestInterfaceFactory
     */
    private $salableQtyRequestFactory;

    /**
     * @param Template\Context $context
     * @param BillingFrequencyRepository $frequencyRepository
     * @param CurrencyInterface $currency
     * @param OrderRepository $orderRepository
     * @param DescriptionCreator $descriptionCreator
     * @param FormContext $formContext
     * @param AttributeRepository $productAttributeRepository
     * @param ManagerConfigurable $managerConfigurable
     * @param StockResolverInterface $stockResolver
     * @param AreProductsSalableForRequestedQtyInterface $productsSalableForRequestedQty
     * @param IsProductSalableForRequestedQtyRequestInterfaceFactory $salableQtyRequestFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        BillingFrequencyRepository $frequencyRepository,
        CurrencyInterface $currency,
        OrderRepository $orderRepository,
        DescriptionCreator $descriptionCreator,
        FormContext $formContext,
        AttributeRepository $productAttributeRepository,
        ManagerConfigurable $managerConfigurable,
        StockResolverInterface $stockResolver,
        AreProductsSalableForRequestedQtyInterface $productsSalableForRequestedQty,
        IsProductSalableForRequestedQtyRequestInterfaceFactory $salableQtyRequestFactory,
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
        $this->stockResolver = $stockResolver;
        $this->productsSalableForRequestedQty = $productsSalableForRequestedQty;
        $this->salableQtyRequestFactory = $salableQtyRequestFactory;
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
     * @return array
     */
    public function getSubscriptionsWithStock()
    {
        if ($this->hasData('in_stock_profiles') && $this->hasData('out_of_stock_profiles')) {
            return [
                'in_stock' => $this->getData('in_stock_profiles'),
                'out_of_stock' => $this->getData('out_of_stock_profiles')
            ];
        }
        $stockSkuRequests = [];
        foreach ($this->getSubscriptionProfiles() as $profile) {
            $profileProducts = $profile->getVisibleProducts();
            $profileProduct = reset($profileProducts);
            $qty = $profileProduct->getQty();
            $sku = $profileProduct->getSku();
            /** In case of configurable product, get SKU from child */
            foreach ($profileProduct->getChildren() as $child) {
                $sku = $child->getSku();
            }
            $stockSkuRequests[] = $this->salableQtyRequestFactory->create(
                [
                    'sku' => $sku,
                    'qty' => $qty
                ]
            );
        }
        try {
            $stockSkuResults = $this->productsSalableForRequestedQty->execute(
                $stockSkuRequests,
                $this->getWebsiteStockId()
            );
        } catch (LocalizedException $e) {
            $stockSkuResults = null;
        }
        $inStockProfiles = [];
        $outOfStockProfiles = [];
        foreach ($this->getSubscriptionProfiles() as $profile) {
            if (!$stockSkuResults) {
                $outOfStockProfiles[] = $profile;
                continue;
            }
            $profileProducts = $profile->getVisibleProducts();
            $profileProduct = reset($profileProducts);
            $sku = $profileProduct->getSku();
            foreach ($profileProduct->getChildren() as $child) {
                $sku = $child->getSku();
            }
            foreach ($stockSkuResults as $stockSkuResult) {
                if ($stockSkuResult->getSku() === $sku) {
                    if ($stockSkuResult->isSalable()) {
                        $inStockProfiles[] = $profile;
                        continue;
                    }
                    $outOfStockProfiles[] = $profile;
                }
            }
        }
        $this->setData('in_stock_profiles', $inStockProfiles);
        $this->setData('out_of_stock_profiles', $outOfStockProfiles);
        return [
            'in_stock' => $this->getData('in_stock_profiles'),
            'out_of_stock' => $this->getData('out_of_stock_profiles')
        ];
    }

    /**
     * @return int|null
     */
    public function getWebsiteStockId()
    {
        if (!$this->getSubscriptionProfiles()) {
            return null;
        }
        $profiles = $this->getSubscriptionProfiles();
        $firstProfile = reset($profiles);
        try {
            $stockId = $this->stockResolver->execute(
                SalesChannelInterface::TYPE_WEBSITE,
                $firstProfile->getWebsite()->getCode()
            )->getStockId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return $stockId;
    }

    /**
     * @return \DateTime
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws LocalizedException
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
     * @throws LocalizedException
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
     * @param $value
     * @return string
     * @throws \Zend_Currency_Exception
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
