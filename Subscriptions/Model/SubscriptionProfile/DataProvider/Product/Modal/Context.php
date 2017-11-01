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
}
