<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Class Products
 */
class Products extends BaseSummary
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
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * Products constructor.
     * @param Template\Context $context
     * @param \Magento\Catalog\Helper\ImageFactory $imageFactory
     * @param \TNW\Subscriptions\Model\BillingFrequencyRepository $frequencyRepository
     * @param \Magento\Framework\Locale\CurrencyInterface $currency
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Catalog\Helper\ImageFactory $imageFactory,
        \TNW\Subscriptions\Model\BillingFrequencyRepository $frequencyRepository,
        \Magento\Framework\Locale\CurrencyInterface $currency,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->imageFactory = $imageFactory;
        $this->frequencyRepository = $frequencyRepository;
        $this->currency = $currency;
        $this->orderRepository = $orderRepository;
        $this->descriptionCreator = $descriptionCreator;
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
        $presetQty = (int)$item->getTnwSubscrUnlockPresetQty();

        return $presetQty
            ? $item->getPrice() * $item->getQty()
            : $item->getPrice();
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
                'totalPrice' => $this->orderRepository->get($orderData['magento_order_id'])->getGrandTotal(),
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
}
