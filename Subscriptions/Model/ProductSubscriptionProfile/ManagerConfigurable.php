<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager\Configurable as ConfigurableTypeManager;
use TNW\Subscriptions\Model\ProductSubscriptionProfileRepository;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Model to manage configurable products.
 */
class ManagerConfigurable
{
    /**
     * Subscription profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * Subscription profile manager
     *
     * @var ProductSubscriptionProfileRepository
     */
    private $subproductRepository;

    /**
     * @var DataObjectFactory
     */
    private $objectFactory;

    /**
     * @var ConfigurableTypeManager
     */
    private $configurableTypeManager;

    /**
     * @param ProfileManager $profileManager
     * @param ProductSubscriptionProfileRepository $subproductRepository
     * @param DataObjectFactory $objectFactory
     * @param ConfigurableTypeManager $configurableTypeManager
     */
    public function __construct(
        ProfileManager $profileManager,
        ProductSubscriptionProfileRepository $subproductRepository,
        DataObjectFactory $objectFactory,
        ConfigurableTypeManager $configurableTypeManager
    ) {
        $this->profileManager = $profileManager;
        $this->subproductRepository = $subproductRepository;
        $this->objectFactory = $objectFactory;
        $this->configurableTypeManager = $configurableTypeManager;
    }

    /**
     * Return item's configurable options data.
     *
     * @param ProductSubscriptionProfile $item
     * @return array
     */
    public function getConfigurableOptionsData(ProductSubscriptionProfile $item)
    {
        $result = [];
        $itemChildren = $item->getChildren();

        if ($itemChildren) {
            $magentoProduct = $this->getProductFromItem($item);

            if ($magentoProduct !== null) {
                $productSuperAttributes = $this->getProductSuperAttributes($magentoProduct);
                $storeId = $magentoProduct->getStoreId();

                if ($magentoProduct->getTypeId() === ConfigurableProduct::TYPE_CODE
                    && $productSuperAttributes->getSize() > 0)
                {
                    //We have to get custom options from child items
                    foreach ($itemChildren as $itemChild) {
                        $customOptions = $itemChild->getCustomOptions();

                        if ($customOptions) {
                            $decodedOptions = \Zend_Json::decode($customOptions);

                            foreach ($productSuperAttributes as $attribute) {
                                $productAttribute = $attribute->getProductAttribute();
                                $attributeId = $productAttribute->getId();

                                if (isset($decodedOptions[$attributeId])) {
                                    foreach ($attribute->getOptions() as $option) {
                                        if ($option['value_index'] == $decodedOptions[$attributeId]) {
                                            $optionLabel = $option['store_label'];
                                            break;
                                        }
                                    }

                                    $result[] = [
                                        'attributeId' => $attributeId,
                                        'attributeLabel' => $productAttribute->getStoreLabel($storeId),
                                        'optionLabel' => $optionLabel,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Return product from subscription profile item.
     *
     * @param ProductSubscriptionProfileInterface $item
     * @return \Magento\Catalog\Model\Product|null
     */
    private function getProductFromItem(ProductSubscriptionProfileInterface $item)
    {
        try {
            return $item->getMagentoProduct();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Return current product super attributes.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\Collection
     */
    private function getProductSuperAttributes(\Magento\Catalog\Model\Product $product)
    {
        return $product->getTypeInstance()->getConfigurableAttributes($product);
    }

    /**
     * Process update profile with configurable product's data.
     *
     * @param array $request
     * @return SubscriptionProfile|string
     */
    public function processProfileUpdate(array $request)
    {
        $profileChanged = false;
        $profile = $this->profileManager->getProfile();
        if (isset($request['sub_product_id'])) {
            $subProductId = $request['sub_product_id'];
            $itemIndex = 'item_' . $subProductId;

            if (isset($request[$itemIndex])) {
                $request = $request[$itemIndex];
            }

            if (!$profile->getId()) {
                $subProduct = $this->subproductRepository->getById($subProductId);
                $profileId = $subProduct->getSubscriptionProfileId();
                /** @var SubscriptionProfile $profile */
                $profile = $this->profileManager->loadProfile($profileId);
                $profile->setDataChanges(false);
            }

            $profileVisibleProducts = $profile->getVisibleProducts();
            $profileProducts = $profile->getProducts();
            $updatedSubProduct = '';

            foreach ($profileVisibleProducts as $profileVisibleProduct) {
                if ($profileVisibleProduct->getId() === $subProductId) {
                    $updatedSubProduct = $profileVisibleProduct;
                    break;
                }
            }

            if ($updatedSubProduct) {
                $magentoProduct = $updatedSubProduct->getMagentoProduct();

                if ($magentoProduct->getTypeId() === ConfigurableProduct::TYPE_CODE) {
                    if (isset($request['subscribe_qty'])) {
                        $request['qty'] = $request['subscribe_qty'];
                    }
                    $request = $this->objectFactory->create($request);
                    $candidates =  $magentoProduct->getTypeInstance()
                        ->prepareForCartAdvanced($request, $magentoProduct, AbstractType::PROCESS_MODE_FULL);

                    /** $candidates is error message */
                    if (is_string($candidates) || $candidates instanceof \Magento\Framework\Phrase) {
                        return strval($candidates);
                    }

                    $price = $this->getSubscriptionItemPrice($request, $profile, $magentoProduct);

                    foreach ($candidates as $candidate) {
                        if ($candidate->getId() === $updatedSubProduct->getMagentoProductId()) {
                            //if $candidate is current updated product
                            $updatedSubProduct
                                ->setDataChanges(false)
                                ->setQty($candidate->getQty())
                                ->setCustomOptions(\Zend_Json::encode($request->getSuperAttribute()))
                                ->setPrice($price);
                            $profileChanged = $profileChanged || $updatedSubProduct->hasDataChanges();
                        } else {
                            //if $candidate is a configurable child product.
                            foreach ($profileProducts as $profileProduct) {
                                if ($profileProduct->getParentId() === $updatedSubProduct->getId()) {
                                    $profileProduct->setMagentoProductId($candidate->getId())
                                        ->setSku($candidate->getSku())
                                        ->setName($candidate->getName())
                                        ->setCustomOptions(\Zend_Json::encode($request->getSuperAttribute()))
                                        ->setQty($candidate->getQty());
                                    $profileChanged = $profileChanged || $profileProduct->hasDataChanges();
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            $profile->setDataChanges($profileChanged);
        }

        return $profile;
    }

    /**
     * Return calculated price for subscription item.
     *
     * @param DataObject $request
     * @param SubscriptionProfile $profile
     * @param Product $magentoProduct
     * @return float|string
     */
    private function getSubscriptionItemPrice(
        DataObject $request,
        SubscriptionProfile $profile,
        Product $magentoProduct
    ) {
        $requestData = $request->getData();
        $requestData['billing_frequency'] = $profile->getBillingFrequencyId();
        if (isset($requestData['price'])) {
            unset($requestData['price']);
        }

        $price = $this->configurableTypeManager->getSubscriptionPrice($magentoProduct, $requestData);

        return $price;
    }
}
