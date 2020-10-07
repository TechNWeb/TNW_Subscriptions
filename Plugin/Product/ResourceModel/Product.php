<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Plugin\Product\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\CouldNotDeleteException;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile as ResourceProductSubscriptionProfile;
use TNW\Subscriptions\Model\Source\ProfileStatus;

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
}
