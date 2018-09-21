<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Cron\Quote;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface as SubscriptionProduct;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Collection;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\CollectionFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\ProcessInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as RelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Class Base
 */
abstract class Base implements ProcessInterface
{
    /**
     * Repository for retrieving subscription profiles.
     *
     * @var SubscriptionProfileRepository
     */
    protected $profileRepository;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    protected $criteriaBuilder;

    /**
     * Subscriptions context.
     *
     * @var Context
     */
    protected $context;

    /**
     * Subscriptions config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Repository fore saving/retrieving quotes.
     *
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Factory for creating subscription collection.
     *
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Profile relation manager.
     *
     * @var RelationManager
     */
    protected $relationManager;

    /**
     * @param SubscriptionProfileRepository $profileRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param Context $context
     * @param Config $config
     * @param CartRepositoryInterface $cartRepository
     * @param CollectionFactory $collectionFactory
     * @param RelationManager $relationManager
     */
    public function __construct(
        SubscriptionProfileRepository $profileRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        Context $context,
        Config $config,
        CartRepositoryInterface $cartRepository,
        CollectionFactory $collectionFactory,
        RelationManager $relationManager
    ) {
        $this->profileRepository = $profileRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->context = $context;
        $this->config = $config;
        $this->cartRepository = $cartRepository;
        $this->collectionFactory = $collectionFactory;
        $this->relationManager = $relationManager;
    }

    /**
     * Returns list of profiles ids to process.
     *
     * @param int $websiteId
     * @return array
     */
    abstract public function getProfilesIdsToProcess($websiteId);

    /**
     * Returns base collection.
     *
     * @return Collection
     */
    protected function getBaseCollection()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->joinTable(
            ['products' => $collection->getTable(SubscriptionProduct::ENTITY_TABLE)],
            SubscriptionProduct::SUBSCRIPTION_PROFILE_ID . ' = ' . SubscriptionProfileInterface::ID,
            ['products_need_recollect' => SubscriptionProduct::NEED_RECOLLECT]
        )->groupByAttribute(
            SubscriptionProfileInterface::ID
        );

        return $collection;
    }

    /**
     * Returns list of profiles with no quotes.
     *
     * @param int $websiteId
     * @return SubscriptionProfileInterface[]
     */
    protected function getProfiles($websiteId)
    {
        $result = [];
        $ids = $this->getProfilesIdsToProcess($websiteId);
        if (!empty($ids)) {
            $this->criteriaBuilder->addFilter(
                SubscriptionProfileInterface::ID,
                $ids,
                'in'
            );
            /** @var SearchCriteriaInterface $searchCriteria */
            $searchCriteria = $this->criteriaBuilder->create();
            $result = $this->profileRepository->getList($searchCriteria)->getItems();
        }

        return $result;
    }

    /**
     * Returns request for adding product to subscription quote.
     *
     * @param SubscriptionProduct $profileProduct
     * @return DataObject
     */
    protected function getProductAddRequest(
        SubscriptionProduct $profileProduct
    ) {
        $data = [
            'custom_price' => $profileProduct->getUnitPrice(),
            'qty' => $profileProduct->getQty(),
            Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                Create::UNIQUE => [
                    'use_preset_qty' => $profileProduct->getTnwSubscrUnlockPresetQty(),
                ],
                Create::NON_UNIQUE => [
                    'current_preset_qty_price' => $profileProduct->getPrice(),
                ],
                Create::FULL_REQUEST_PARAM_NAME => false,
            ]
        ];
        $productType = $profileProduct->getMagentoProduct()->getTypeId();
        switch ($productType) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
            case \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE:
                break;
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $data = $this->addConfigurableOptions(
                    $profileProduct,
                    $data
                );
                break;
            default:
                throw new \InvalidArgumentException(__('Unsupported product type -' . $productType));
                break;
        }

        return new DataObject($data);
    }

    /**
     * Updates quote for profile.
     *
     * @param SubscriptionProfileInterface $profile
     * @param Quote $quote
     * @return Quote
     */
    protected function processQuote(SubscriptionProfileInterface $profile, Quote $quote)
    {
        //Deactivate quote
        $quote->setIsActive(false);
        $quote->setData('ignore_old_qty', true);
        $quote->setData('is_super_mode', true);
        $quote->setData('scheduled', true);
        //Set store
        $quote->setStore(
            $profile->getWebsite()->getDefaultStore()
        );
        //Set currency
        $quote->setQuoteCurrencyCode($profile->getProfileCurrencyCode());
        //Set customer
        if (!$quote->getCustomerId()) {
            $quote->assignCustomer($profile->getCustomer());
        }
        //Add products
        $this->addProductsToQuote($profile, $quote);
        //Set shipping address
        $quote->getShippingAddress()->addData(
            $profile->getShippingAddress()->getData()
        );
        $quote->getShippingAddress()->setCustomerId(
            $profile->getCustomerId()
        );
        //Set billing address
        $quote->getBillingAddress()->addData(
            $profile->getBillingAddress()->getData()
        );
        $quote->getBillingAddress()->setCustomerId(
            $profile->getCustomerId()
        );
        //Set shipping method
        $quote->getShippingAddress()
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($profile->getShippingMethod());
        $quote->setTotalsCollectedFlag(false);
        $this->cartRepository->save($quote);

        return $quote;
    }

    /**
     * Adds products to quote.
     *
     * @param SubscriptionProfileInterface $profile
     * @param Quote $quote
     * @return void
     */
    protected function addProductsToQuote(SubscriptionProfileInterface $profile, Quote $quote)
    {
        foreach ($profile->getVisibleProducts() as $profileProduct) {
            $addRequest = $this->getProductAddRequest($profileProduct);
            $quote->addProduct($profileProduct->getMagentoProduct(), $addRequest);
        }
    }

    /**
     * Adds conf. options to buy request.
     *
     * @param SubscriptionProduct $profileProduct
     * @param array $data
     * @return array
     * @throws \InvalidArgumentException
     */
    private function addConfigurableOptions(SubscriptionProduct $profileProduct, array $data)
    {
        $options = [];
        /** @var SubscriptionProduct $child */
        foreach ($profileProduct->getChildren() as $child) {
            $options += array_replace(
                $options,
                \Zend_Json::decode($child->getCustomOptions()) ?: []
            );
        }
        if (empty($options)) {
            throw new \InvalidArgumentException(__('Custom options must be set.'));
        }
        $data['super_attribute'] = $options;

        return $data;
    }
}
