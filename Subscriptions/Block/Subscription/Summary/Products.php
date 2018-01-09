<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;
use TNW\Subscriptions\Model\Context as SubscriptionContext;

/**
 * Class Products
 */
class Products extends BaseSummary
{
    /**
     * Product types that can be configured.
     */
    const CONFIGURE_TYPES = [
        ConfigurableProduct::TYPE_CODE,
    ];

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
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * @var SubscriptionContext
     */
    private $subscriptionContext;

    /**
     * Products constructor.
     * @param Template\Context $context
     * @param \Magento\Catalog\Helper\ImageFactory $imageFactory
     * @param \TNW\Subscriptions\Model\BillingFrequencyRepository $frequencyRepository
     * @param \Magento\Framework\Locale\CurrencyInterface $currency
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator
     * @param SubscriptionContext $subscriptionContext
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Catalog\Helper\ImageFactory $imageFactory,
        \TNW\Subscriptions\Model\BillingFrequencyRepository $frequencyRepository,
        \Magento\Framework\Locale\CurrencyInterface $currency,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator,
        SubscriptionContext $subscriptionContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->imageFactory = $imageFactory;
        $this->frequencyRepository = $frequencyRepository;
        $this->currency = $currency;
        $this->orderRepository = $orderRepository;
        $this->descriptionCreator = $descriptionCreator;
        $this->subscriptionContext = $subscriptionContext;
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
    public function getImageUrl($item)
    {
        $imageHelper = $this->subscriptionContext->getImageHelperForSubscriptionProduct($item,'product_small_image');
        return $imageHelper->getUrl();
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
        return $currency->toCurrency(sprintf("%f", $value));
    }

    /**
     * Check if product options can be shown.
     * Depend on product type.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function canShowProductOptions(\Magento\Catalog\Model\Product $product)
    {
        return in_array($product->getTypeId(), self::CONFIGURE_TYPES);
    }

    /**
     * Render item product options.
     *
     * @param ProductSubscriptionProfileInterface $item
     * @return string
     */
    public function renderProductOptions(ProductSubscriptionProfileInterface $item)
    {
        $product = $this->getProductFromItem($item);

        if ($product && $this->canShowProductOptions($product)) {
            $productType = $product->getTypeId();

            $childBlock = $this->getChildBlock($productType . '.product');

            if ($childBlock && $childBlock instanceof \Magento\Framework\View\Element\Template) {
                $childBlock->setItem($item);

                return $childBlock->toHtml();
            }
        }

        return '';
    }
}
