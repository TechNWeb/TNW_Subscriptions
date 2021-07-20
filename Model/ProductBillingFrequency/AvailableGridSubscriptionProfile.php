<?php

namespace TNW\Subscriptions\Model\ProductBillingFrequency;

use Magento\Framework\Api\SearchCriteriaBuilder;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Add Subscription Id to product billing frequency table for generate grid on remove BF from product
 *
 * Class AvailableGridSubscriptionProfile
 * @package TNW\Subscriptions\Model\ProductBillingFrequency
 */
class AvailableGridSubscriptionProfile
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
     * AvailableGridSubscriptionProfile constructor.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductBillingFrequencyRepositoryInterface $productBillingFrequency
     * @param Json $json
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductBillingFrequencyRepositoryInterface $productBillingFrequency,
        Json $json
    )
    {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productBillingFrequency = $productBillingFrequency;
        $this->json = $json;
    }

    /**
     * @param $subscriptionProfile
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDataForUrl($subscriptionProfile)
    {
        $searchStatuses = [
            ProfileStatus::STATUS_ACTIVE,
            ProfileStatus::STATUS_TRIAL,
            ProfileStatus::STATUS_HOLDED,
            ProfileStatus::STATUS_PAST_DUE
        ];
        $ids = [];
        $billingFrequencies = false;

        foreach ($subscriptionProfile->getProducts() as $product) {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                'magento_product_id',
                $product->getMagentoProductId()
            )->addFilter(
                'billing_frequency_id',
                $subscriptionProfile->getBillingFrequencyId()
            )->create();

            $billingFrequencies = $this->productBillingFrequency->getList($searchCriteria)->getItems();
        }

        if ($billingFrequencies) {
            foreach ($billingFrequencies as $frequency) {
                if ($frequency->getSubscProfileIdForGrid()) {
                    $ids = $this->json->unserialize($frequency->getSubscProfileIdForGrid());
                }

                $search = array_search($subscriptionProfile->getId(), $ids);
                if (in_array($subscriptionProfile->getStatus(), $searchStatuses)
                    && $subscriptionProfile->getId()
                    && $search === false
                ) {
                    $subId = $subscriptionProfile->getId();
                    array_push($ids, $subId);
                    $frequency->setSubscProfileIdForGrid($this->json->serialize($ids));
                    if ($frequency->getFlag() !== 1) {
                        $frequency->setFlag(1);
                    }
                } else {
                    if (($key = array_search($subscriptionProfile->getId(), $ids)) !== false
                        && !in_array($subscriptionProfile->getStatus(), $searchStatuses)
                    ) {
                        unset($ids[$key]);
                        $frequency->setSubscProfileIdForGrid($this->json->serialize($ids));
                    }
                    if (!$ids) {
                        $frequency->setFlag(0);
                    }
                }
                $this->productBillingFrequency->save($frequency);
            }
        }
    }
}
