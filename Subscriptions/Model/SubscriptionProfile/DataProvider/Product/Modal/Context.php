<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManager\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as RecurringOptionRepository;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;

/**
 * Data providers form context.
 */
class Context implements ContextInterface
{
    /**
     * Repository for retrieving products.
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Repository for retrieving product billing frequencies.
     *
     * @var RecurringOptionRepository
     */
    private $recurringOptionRepository;

    /**
     * Help to retrieve data from request(GET, POST).
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * Repository for retrieving billing frequencies.
     *
     * @var BillingFrequencyRepository
     */
    private $frequencyRepository;

    /**
     * Store Manager.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Subscription session.
     *
     * @var QuoteSessionInterface
     */
    private $sessionQuote;

    /**
     * * Factory for creating currencies.
     *
     * @var CurrencyFactory
     */
    private $currencyFactory;

    /**
     * Image helper.
     *
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * Help convert trial unit value into label.
     *
     * @var TrialLengthUnitType
     */
    private $unitType;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param RecurringOptionRepository $repository
     * @param BillingFrequencyRepository $frequencyRepository
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param QuoteSessionInterface $sessionQuote
     * @param CurrencyFactory $currencyFactory
     * @param ImageHelper $imageHelper
     * @param TrialLengthUnitType $unitType
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        RecurringOptionRepository $repository,
        BillingFrequencyRepository $frequencyRepository,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        QuoteSessionInterface $sessionQuote,
        CurrencyFactory $currencyFactory,
        ImageHelper $imageHelper,
        TrialLengthUnitType $unitType
    ) {
        $this->productRepository = $productRepository;
        $this->recurringOptionRepository = $repository;
        $this->frequencyRepository = $frequencyRepository;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->sessionQuote = $sessionQuote;
        $this->currencyFactory = $currencyFactory;
        $this->imageHelper = $imageHelper;
        $this->unitType = $unitType;
    }

    /**
     * Returns product repository.
     *
     * @return ProductRepositoryInterface
     */
    public function getProductRepository()
    {
        return $this->productRepository;
    }

    /**
     * Returns product billing frequency repository.
     *
     * @return RecurringOptionRepository
     */
    public function getRecurringOptionRepository()
    {
        return $this->recurringOptionRepository;
    }

    /**
     * Returns request object.
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns billing frequency repository.
     *
     * @return BillingFrequencyRepository
     */
    public function getFrequencyRepository()
    {
        return $this->frequencyRepository;
    }

    /**
     * Returns store manager.
     *
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * Returns subscription session.
     *
     * @return QuoteSessionInterface
     */
    public function getSession()
    {
        return $this->sessionQuote;
    }

    /**
     * Returns currency factory.
     *
     * @return CurrencyFactory
     */
    public function getCurrencyFactory()
    {
        return $this->currencyFactory;
    }

    /**
     * Returns image helper.
     *
     * @return ImageHelper
     */
    public function getImageHelper()
    {
        return $this->imageHelper;
    }

    /**
     * Returns trial length unit type source.
     *
     * @return TrialLengthUnitType
     */
    public function getUnitType()
    {
        return $this->unitType;
    }

    /**
     * Retrieve image helper from quote item.
     *
     * @param CartItemInterface $quoteItem
     * @param string $imageId
     * @return ImageHelper|null
     * @throws \InvalidArgumentException
     */
    public function getImageHelperForQuoteItem(CartItemInterface $quoteItem, $imageId = '')
    {
        $imageHelper = $this->getImageHelper();
        $currentProduct = null;
        switch ($quoteItem->getProductType()) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
            case \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE:
                $currentProduct = $quoteItem->getProduct();
                break;
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $quoteItemChildrens = $quoteItem->getChildren();
                $currentProduct = reset($quoteItemChildrens)->getProduct();
                break;
            default:
                throw new \InvalidArgumentException(__('Unsupported product type -' . $quoteItem->getProductType()));
                break;
        }

        $imageHelper = $imageHelper->init(
            $currentProduct,
            $imageId,
            ['type' => 'small_image', 'width' => '240', 'height' => '240']
        );
        return $imageHelper;
    }

    /**
     * Retrieve image helper from subscription product.
     *
     * @param ProductSubscriptionProfileInterface $item
     * @param string $imageId
     * @return ImageHelper|null
     * @throws \InvalidArgumentException
     */
    public function getImageHelperForSubscriptionProduct(ProductSubscriptionProfileInterface $item, $imageId = 'category_page_grid')
    {
        $imageHelper = $this->getImageHelper();
        $currentProduct = null;
        switch ($item->getMagentoProduct()->getTypeId()) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
            case \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE:
                $currentProduct = $item->getMagentoProduct();
                break;
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $quoteItemChildrens = $item->getChildren();
                $currentProduct = reset($quoteItemChildrens)->getMagentoProduct();
                break;
            default:
                throw new \InvalidArgumentException(__('Unsupported product type -' . $item->getTypeId()));
                break;
        }

        $imageHelper = $imageHelper->init(
            $currentProduct,
            $imageId,
            ['type' => 'small_image', 'width' => '240', 'height' => '240']
        );
        return $imageHelper;
    }
}
