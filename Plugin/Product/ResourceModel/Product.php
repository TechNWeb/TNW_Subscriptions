<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Plugin\Product\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Entity\Attribute\Exception as AttributeException;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\ProductBillingFrequency;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile as ResourceProductSubscriptionProfile;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use Magento\Catalog\Model\Product as ProductModel;

/**
 * Plugin for subscription product validation (existence in subscription profile).
 */
class Product
{
    /**
     * Resource model product subscription profile
     *
     * @var ResourceProductSubscriptionProfile
     */
    private $resourceProductSubscriptionProfile;

    /**
     * Profile status source
     *
     * @var ProfileStatus
     */
    private $profileStatus;

    /**
     * @param ResourceProductSubscriptionProfile $productSubscriptionProfile
     * @param ProfileStatus $profileStatus
     */
    public function __construct(
        ResourceProductSubscriptionProfile $productSubscriptionProfile,
        ProfileStatus $profileStatus
    ) {
        $this->resourceProductSubscriptionProfile = $productSubscriptionProfile;
        $this->profileStatus = $profileStatus;
    }

    /**
     * Validate delete product. If product linked to profile and
     * profile not in status Complete or Canceled throw exception
     *
     * @param ProductResource $subject
     * @param ProductInterface $product
     * @throws CouldNotDeleteException
     */
    public function beforeDelete(
        ProductResource $subject,
        ProductInterface $product
    ) {
        $profileIds = $this->resourceProductSubscriptionProfile
            ->getProfileStatusByProductIds([$product->getId()]);
        $availableToDeleteProfileStatus = [
            profileStatus::STATUS_CANCELED,
            ProfileStatus::STATUS_COMPLETE,
        ];
        foreach ($profileIds as $productProfileData) {
            if (!in_array($productProfileData['profile_status'], $availableToDeleteProfileStatus)) {
                throw new CouldNotDeleteException(
                    __(
                        'Product with ID %1 can\'t delete, this product attached to profile with ID %2.'
                        . ' Profile have status %3',
                        $productProfileData['product_id'],
                        $productProfileData['profile_id'],
                        $this->profileStatus->getLabelByValue($productProfileData['profile_status'])
                    )
                );
            }
        }
    }

    /**
     * Validate product billing frequency changes
     *
     * @param ProductResource $subject
     * @param $result
     * @param $partMethod
     * @param array $args
     * @param $collectExceptionMessages
     * @return array|mixed
     * @throws AttributeException
     */
    public function afterWalkAttributes(
        ProductResource $subject,
        $result,
        $partMethod,
        array $args = [],
        $collectExceptionMessages = null
    ) {
        if ($partMethod !== 'backend/validate' || !isset($args[0]) || !($args[0] instanceof ProductModel)) {
            return $result;
        }

        $changedBillingFrequencies = $this->findChangedOrDeletedBillingFrequencies($args[0]);

        if ($changedBillingFrequencies && $this->isBillingFrequenciesUsedBySubscriptionProfiles(
            $subject->getConnection(),
            $args[0]->getId(),
            $changedBillingFrequencies
        )) {
            $attributeCode = 'recurring_options';
            $message = __('Some of changed billing frequencies are used in subscription profiles');

            if ($collectExceptionMessages) {
                if (is_array($result)) {
                    $result[$attributeCode] = $message;
                } else {
                    $result = [$attributeCode => $message];
                }
            } else {
                throw (new AttributeException($message))
                    ->setAttributeCode($attributeCode)
                    ->setPart('backend');
            }
        }

        return $result;
    }

    /**
     * Checks if given billing frequencies is used by subscription profiles
     *
     * @param AdapterInterface $connection
     * @param int $productId
     * @param array $billingFrequencyIds
     * @return bool
     */
    private function isBillingFrequenciesUsedBySubscriptionProfiles(
        AdapterInterface $connection,
        int $productId,
        array $billingFrequencyIds
    ) {
        $select = $connection->select()
            ->from(
                ProductSubscriptionProfileInterface::ENTITY_TABLE,
                'COUNT(*)'
            )->where(
                ProductSubscriptionProfileInterface::MAGENTO_PRODUCT_ID . ' = ?',
                $productId
            )->where(
                "CAST(JSON_EXTRACT(" . ProductSubscriptionProfileInterface::CUSTOM_OPTIONS .
                ", '$.info_buyRequest.billing_frequency') AS UNSIGNED) IN(?)",
                $billingFrequencyIds
            );

        $count = (int) $connection->fetchOne($select);

        return $count > 0;
    }

    /**
     * Finds changed or deleted billing frequency IDs
     *
     * @param ProductModel $productModel
     * @return array
     */
    private function findChangedOrDeletedBillingFrequencies(ProductModel $productModel)
    {
        $oldRecurringOptions = $productModel->getOrigData('recurring_options');
        $newRecurringOptions = $productModel->getData('recurring_options');

        if (!is_array($oldRecurringOptions) || !is_array($newRecurringOptions)) {
            return [];
        }

        $result = [];

        foreach ($oldRecurringOptions as $oldRecurringOption) {
            /** @var ProductBillingFrequency $oldRecurringOption */
            $newRecurringOption = $this->findRecurringOptionById($newRecurringOptions, $oldRecurringOption->getId());
            $oldBillingFrequencyId = $oldRecurringOption->getBillingFrequencyId();

            if ($newRecurringOption === null ||
                $oldBillingFrequencyId != $newRecurringOption->getBillingFrequencyId()) {
                $result[] = (int) $oldBillingFrequencyId;
            }
        }

        return $result;
    }

    /**
     * Finds recurring option in array by ID
     *
     * @param ProductBillingFrequency[] $recurringOptions
     * @param numeric $id
     * @return ProductBillingFrequency|null
     */
    private function findRecurringOptionById($recurringOptions, $id)
    {
        foreach ($recurringOptions as $recurringOption) {
            if ($recurringOption->getId() == $id) {
                return $recurringOption;
            }
        }
        return null;
    }
}
