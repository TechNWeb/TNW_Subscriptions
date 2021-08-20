<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ProductBillingFrequency;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Add Subscription Id to product billing frequency table for generate grid on remove BF from product
 *
 * Class AvailableSubscriptionProfileGrid
 */
class AvailableSubscriptionProfileGrid
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductBillingFrequencyRepositoryInterface
     */
    private $productBillingFrequency;

    /**
     * @var Json
     */
    private $json;

    /**
     * AvailableSubscriptionProfileGrid constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequency
     * @param Json $json
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductBillingFrequencyRepositoryInterface $productBillingFrequency,
        Json $json
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productBillingFrequency = $productBillingFrequency;
        $this->json = $json;
    }

    /**
     * @param $subscriptionProfile
     * @throws LocalizedException
     */
    public function saveSubscriptionProfileIdsForFormingGrid($subscriptionProfile)
    {
        $searchStatuses = [
            ProfileStatus::STATUS_ACTIVE,
            ProfileStatus::STATUS_TRIAL,
            ProfileStatus::STATUS_HOLDED,
            ProfileStatus::STATUS_PAST_DUE,
            ProfileStatus::STATUS_SUSPENDED,
            ProfileStatus::STATUS_PENDING
        ];
        $ids = [];
        $billingFrequencies = false;
        $oldBillingFrequencies = false;

        $oldBillingFrequencyId = $subscriptionProfile->getOrigData('billing_frequency_id')
            ? $subscriptionProfile->getOrigData('billing_frequency_id')
            : $subscriptionProfile->getBillingFrequencyId();

        if ($subscriptionProfile->getBillingFrequencyId() !== $oldBillingFrequencyId) {
            foreach ($subscriptionProfile->getProducts() as $product) {
                $oldBillingFrequencies = $this->getProductBillingFrequency(
                    $product->getMagentoProductId(),
                    $oldBillingFrequencyId
                );
            }
            foreach ($oldBillingFrequencies as $oldFrequency) {
                if ($oldFrequency->getSubscriptionProfileIds()) {
                    $ids = $this->json->unserialize($oldFrequency->getSubscriptionProfileIds());
                }
                $key = array_search($subscriptionProfile->getId(), $ids);
                if ($key !== false) {
                    unset($ids[$key]);
                    $oldFrequency->setSubscriptionProfileIds($this->json->serialize($ids));
                }
                $this->productBillingFrequency->save($oldFrequency);
            }
        }

        foreach ($subscriptionProfile->getProducts() as $product) {
            $billingFrequencies = $this->getProductBillingFrequency(
                $product->getMagentoProductId(),
                $subscriptionProfile->getBillingFrequencyId()
            );
        }

        if ($billingFrequencies) {
            foreach ($billingFrequencies as $frequency) {
                if ($frequency->getSubscriptionProfileIds()) {
                    $ids = $this->json->unserialize($frequency->getSubscriptionProfileIds());
                }

                $search = array_search($subscriptionProfile->getId(), $ids);
                if (in_array($subscriptionProfile->getStatus(), $searchStatuses)
                    && $subscriptionProfile->getId()
                    && $search === false
                ) {
                    $subId = $subscriptionProfile->getId();
                    array_push($ids, $subId);
                    $frequency->setSubscriptionProfileIds($this->json->serialize($ids));
                } else {
                    $key = array_search($subscriptionProfile->getId(), $ids);
                    if ($key !== false && !in_array($subscriptionProfile->getStatus(), $searchStatuses)) {
                        unset($ids[$key]);
                        $frequency->setSubscriptionProfileIds($this->json->serialize($ids));
                    }
                }
                $this->productBillingFrequency->save($frequency);
            }
        }
    }

    /**
     * @param $magentoProductId
     * @param $billingFrequencyId
     * @return ProductBillingFrequencyInterface[]
     * @throws LocalizedException
     */
    private function getProductBillingFrequency($magentoProductId, $billingFrequencyId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'magento_product_id',
            $magentoProductId
        )->addFilter(
            'billing_frequency_id',
            $billingFrequencyId
        )->create();

        return $this->productBillingFrequency->getList($searchCriteria)->getItems();
    }
}
