<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Plugin\Product;


use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile AS ResourceProductSubscriptionProfile;
use TNW\Subscriptions\Model\Source\ProfileStatus;

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
     * prepare recurring options before product save
     *
     * @param ProductInterface $product
     *
     * @return ProductInterface
     */
    public function beforeSave(ProductInterface $product)
    {
        /**
         * $this->getCanSaveRecurringOptions() - set either in controller when "Recurring Options" ajax tab is loaded,
         * or in type instance as well
         */
        if ($product->getCanSaveRecurringOptions()) {
            $options = $product->getData('recurring_options');
            if (is_array($options)) {
                $product->setIsRecurringOptionChanged(true);
                foreach ($options as $option) {
                    if ($option instanceof ProductBillingFrequencyInterface) {
                        $option = $option->getData();
                    }
                    if (!isset($option['is_delete']) || $option['is_delete'] != '1') {
                        $product->setHasRecurringOptions(true);
                    }
                }
            }
        }

        return [$product];
    }

    /**
     * Validate delete product. If product linked to profile and
     * profile not in status Complete or Canceled throw exception
     *
     * @param ProductInterface $product
     * @throws \Exception
     */
    public function beforeDelete(ProductInterface $product)
    {
        $profileIds = $this->resourceProductSubscriptionProfile
            ->getProfileStatusByProductIds([$product->getId()]);
        $availableToDeleteProfileStatus = [
            profileStatus::STATUS_CANCELED,
            ProfileStatus::STATUS_COMPLETE,
        ];
        foreach ($profileIds as $productProfileData) {
            if (!in_array($productProfileData['profile_status'], $availableToDeleteProfileStatus)) {
                throw new CouldNotDeleteException(
                    __('Product with ID %1 can\'t delete, this product attached to profile with ID %2. Profile have status %3',
                        $productProfileData['product_id'],
                        $productProfileData['profile_id'],
                        $this->profileStatus->getLabelByValue($productProfileData['profile_status'])
                    )
                );
            }
        }
    }
}
