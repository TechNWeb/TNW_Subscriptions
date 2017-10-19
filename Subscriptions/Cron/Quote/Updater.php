<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Cron\Quote;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\CollectionFactory;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as RelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface as ProfileRelation;

/**
 * Class Updater
 */
class Updater extends Base
{
    /**
     * Updater constructor.
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
        parent::__construct($profileRepository, $criteriaBuilder, $context, $config, $cartRepository,
            $collectionFactory, $relationManager);
    }


    /**
     * @inheritdoc
     */
    public function getProfilesIdsToProcess($websiteId)
    {
        $collection = $this->getBaseCollection();
        $collection->addFieldToFilter(
            SubscriptionProfileInterface::WEBSITE_ID,
            $websiteId
        )->addFieldToFilter(
            [
                ['attribute' => SubscriptionProfileInterface::NEED_RECOLLECT, 'eq' => 1],
                ['attribute' => 'products_need_recollect', 'eq' => 1]
            ]
        );

        return $collection->getAllIds();
    }

    /**
     * Recalculates profile quotes if profile or profile product was changed.
     *
     * @param array $data
     * @return void
     */
    public function process(array $data)
    {
        foreach ($data as $websiteId) {
            foreach ($this->getProfiles($websiteId) as $profile) {
                try {
                    foreach ($this->getProfileQuotes($profile) as $profileQuote) {
                        $this->prepareQuote($profileQuote);
                        $this->processQuote(
                            $profile,
                            $profileQuote
                        );
                    }
                    $this->updateProfileCollectFlag($profile, false);
                } catch (\Exception $e) {
                    $this->context->log('Error on quotes recalculation for profile - ' . $profile->getId());
                    $this->context->log($e->getMessage());
                }
                $this->profileRepository->save($profile);
            }
        }
    }

    /**
     * Returns future profile quotes.
     *
     * @param SubscriptionProfileInterface $profile
     * @return Quote[]
     */
    private function getProfileQuotes(SubscriptionProfileInterface $profile)
    {
        $result = [];
        $relations = $this->relationManager->getNextProfileRelation($profile, true);
        $quoteIds = array_map(
            function (ProfileRelation $relation) {
                return $relation->getMagentoQuoteId();
            },
            $relations
        );
        if (!empty($quoteIds)){
            $this->criteriaBuilder->addFilter(
                SubscriptionProfileInterface::ID,
                $quoteIds,
                'in'
            );
            /** @var SearchCriteria $searchCriteria */
            $searchCriteria = $this->criteriaBuilder->create();
            $result = $this->cartRepository->getList($searchCriteria)->getItems();
        }

        return $result;
    }

    /**
     * @param SubscriptionProfileInterface $profile
     * @param $value
     * @return void
     */
    private function updateProfileCollectFlag(SubscriptionProfileInterface $profile, $value)
    {
        foreach ($profile->getProducts() as $product){
            $product->setNeedRecollect($value);
        }
        $profile->setNeedRecollect($value);
    }

    /**
     * Clears profile quote.
     *
     * @param Quote $profileQuote
     * @return void
     */
    private function prepareQuote($profileQuote)
    {
        $profileQuote->removeAllAddresses();
        $profileQuote->removeAllItems();
        $profileQuote->removePayment();
    }


    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        return [];
    }
}
