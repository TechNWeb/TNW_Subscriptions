<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Product\ListProduct;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as FrequencyRepository;
use TNW\Subscriptions\Model\Config\Product\SubscriptionProductView;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as FrequencyOptionRepository;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Form as ModalForm;
use Magento\Framework\Json\Encoder;

/**
 *  Subscription product list action buttons.
 */
class ListProductButtons extends Template
{
    /**
     * Subscription Product View Config model.
     *
     * @var SubscriptionProductView
     */
    private $subscriptionProductViewConfig;

    /**
     * @var FrequencyRepository
     */
    private $frequencyRepository;

    /**
     * @var FrequencyOptionRepository
     */
    private $frequencyOptionRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Encoder
     */
    private $encoder;

    /**
     * @param Template\Context $context
     * @param SubscriptionProductView $subscriptionProductViewConfig
     * @param FrequencyOptionRepository $frequencyOptionRepository
     * @param FrequencyRepository $frequencyRepository
     * @param Config $config
     * @param Encoder $encoder
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SubscriptionProductView $subscriptionProductViewConfig,
        FrequencyOptionRepository $frequencyOptionRepository,
        FrequencyRepository $frequencyRepository,
        Config $config,
        Encoder $encoder,
        array $data = []
    ) {
        $this->subscriptionProductViewConfig = $subscriptionProductViewConfig;
        $this->frequencyOptionRepository = $frequencyOptionRepository;
        $this->frequencyRepository = $frequencyRepository;
        $this->config = $config;
        $this->encoder = $encoder;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current product.
     *
     * @return ProductInterface|null
     */
    public function getCurrentProduct()
    {
        return $this->getProduct() ?: null;
    }

    /**
     * Return position for actions regarding image size changing in vde if needed.
     *
     * @return string|null
     */
    public function getCurrentPosForActions()
    {
        return $this->getPos() ?: null;
    }

    /**
     * Return current list view mode.
     *
     * @return string
     */
    public function getCurrentViewMode()
    {
        return $this->getViewMode() ?: "";
    }

    /**
     * Return current product in list position.
     *
     * @return string
     */
    public function getCurrentPosition()
    {
        return $this->getPosition() ?: "";
    }

    /**
     * Return current product post params.
     *
     * @return array
     */
    public function getCurrentPostParams()
    {
        return $this->getPostParams() ?: [];
    }

    /**
     * Get "Enable Subscriptions" config value for current website.
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function isSubscribeAvailable(ProductInterface $product)
    {
        return $this->subscriptionProductViewConfig->isSubscribeAvailable($product);
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" only.
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function isOnlySubscribePurchase(ProductInterface $product)
    {
        return $this->subscriptionProductViewConfig->isOnlySubscribePurchase($product);
    }

    /**
     * Check if subscription purchase type is "One time purchase only" only.
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function isOneTimePurchase(ProductInterface $product)
    {
        return ($product->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE) == PurchaseType::ONE_TIME_PURCHASE_TYPE)
            && $product->getIsSalable();
    }

    /**
     * Returns product billing frequencies as array.
     *
     * @return array
     */
    public function getFrequencyOption()
    {
        $productFrequency = $this->getProductBillingFrequencies();

        if (is_null($productFrequency)) {
            return [];
        }

        $frequency = $this->frequencyRepository->getById($productFrequency['billing_frequency_id']);
        $preconfigured = $this->preconfiguredValue('subscription_data/unique/billing_frequency');
        $isDefault = null === $preconfigured
            ? $productFrequency['default_billing_frequency']
            : $preconfigured == $productFrequency['billing_frequency_id'];

        $data = [
            'label' => $frequency->getLabel(),
            'value' => $productFrequency['billing_frequency_id'],
            'frequency_unit' => $frequency->getFrequency(),
            'frequency_unit_type' => $frequency->getUnit(),
            'is_default' => $isDefault,
        ];

        if (!$this->getAllowEditSubscribeQty()) {
            $data['preset_qty'] = $productFrequency['preset_qty'];
        }

        return $data;
    }

    /**
     * Returns list of product billing frequencies.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProductBillingFrequencies()
    {
        if (is_null($this->getData('product_billing_frequencies'))) {
            $productId = $this->getProduct()->getId();
            $productBillingFrequencies = $this->frequencyOptionRepository
                ->getListByProductId($productId)
                ->getItems();
            foreach ($productBillingFrequencies as $productBillingFrequency) {
                if ($productBillingFrequency->getData()['default_billing_frequency'] ==  1) {
                    $this->setData('product_billing_frequencies', $productBillingFrequency);
                }
            }
        }

        return $this->getData('product_billing_frequencies');
    }

    /**
     * Can allow edit Subscribe Qty
     *
     * @return bool
     */
    public function getAllowEditSubscribeQty()
    {
        return !(bool) $this->getProduct()->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
    }

    /**
     * Get default value for Subscribe Qty
     *
     * @return int
     */
    public function getDefaultSubscribeQty()
    {
        if (!$this->getAllowEditSubscribeQty()) {
            if ($this->getFrequencyOption()['is_default'] && isset($option['preset_qty'])) {
                return $this->getFrequencyOption()['preset_qty'] * 1;
            }
        }

        return $this->preconfiguredValue('qty') ?: 1;
    }

    /**
     * Get default period value
     *
     * @return string
     */
    public function getDefaultPeriod()
    {
        $period = $this->preconfiguredValue('subscription_data/unique/period');

        return null !== $period
            ? (string) $period
            : ModalForm::DEFAULT_PERIOD_VALUE;
    }

    /**
     * Get is need check until canceled by default
     *
     * @return string
     */
    public function getDefaultUntilCancelled()
    {
        $term = $this->preconfiguredValue('subscription_data/unique/term') ?? $this->config->isUntilCanceledChecked();
        return $this->getIsInfiniteSubscriptions() ?: $term;
    }

    /**
     * Check for preconfigured value
     *
     * @param string $field
     * @return mixed
     */
    private function preconfiguredValue($field)
    {
        return $this->getProduct()->hasPreconfiguredValues()
            ? $this->getProduct()->getPreconfiguredValues()->getData($field)
            : null;
    }

    /**
     * Get is only infinite subscriptions available.
     *
     * @return string
     */
    public function getIsInfiniteSubscriptions()
    {
        return $this->getProduct()->getData(Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS);
    }

    /**
     * Get array of options to add in cart from category page
     *
     * @return array
     */
    public function getSubscribeOptions()
    {
        $data = $this->getFrequencyOption();
        if ($this->getIsInfiniteSubscriptions()) {
            $data['term'] = $this->getIsInfiniteSubscriptions();
        } else {
            $data['term'] = $this->getDefaultUntilCancelled();
        }
        $data['period'] = $this->getDefaultPeriod();
        $data['subscribe_qty'] = $this->getDefaultSubscribeQty();
        return $data;
    }

    /**
     * Encode subscribe options
     *
     * @param $options
     * @return string
     */
    public function getSubscribeOptionsJson($options)
    {
        return $this->encoder->encode($options);
    }
}
