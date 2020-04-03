<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
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
     * @var Product\OptionFactory
     */
    private $productOptionFactory;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    private $stringUtils;

    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
    private $filterManager;

    /**
     * @param ProfileManager $profileManager
     * @param ProductSubscriptionProfileRepository $subproductRepository
     * @param DataObjectFactory $objectFactory
     * @param ConfigurableTypeManager $configurableTypeManager
     * @param Product\OptionFactory $productOptionFactory
     * @param \Magento\Framework\Stdlib\StringUtils $stringUtils
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     */
    public function __construct(
        ProfileManager $profileManager,
        ProductSubscriptionProfileRepository $subproductRepository,
        DataObjectFactory $objectFactory,
        ConfigurableTypeManager $configurableTypeManager,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Framework\Stdlib\StringUtils $stringUtils,
        \Magento\Framework\Filter\FilterManager $filterManager
    ) {
        $this->profileManager = $profileManager;
        $this->subproductRepository = $subproductRepository;
        $this->objectFactory = $objectFactory;
        $this->configurableTypeManager = $configurableTypeManager;
        $this->productOptionFactory = $productOptionFactory;
        $this->stringUtils = $stringUtils;
        $this->filterManager = $filterManager;
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
        foreach ($this->getItemOptions($item) as $itemOption) {
            $value = $this->getFormatedOptionValue($itemOption);

            $result[] = [
                'attributeLabel' => $itemOption['label'],
                'optionLabel' => isset($formatedOptionValue['full_view']) ? $value['full_view'] : $value['value'],
            ];
        }

        return $result;
    }

    /**
     * @param $optionValue
     * @return array
     */
    public function getFormatedOptionValue($optionValue)
    {
        $optionInfo = [];

        // define input data format
        if (is_array($optionValue)) {
            if (isset($optionValue['option_id'])) {
                $optionInfo = $optionValue;
                if (isset($optionInfo['value'])) {
                    $optionValue = $optionInfo['value'];
                }
            } elseif (isset($optionValue['value'])) {
                $optionValue = $optionValue['value'];
            }
        }

        // render customized option view
        if (isset($optionInfo['custom_view']) && $optionInfo['custom_view']) {
            $default = ['value' => $optionValue];
            if (isset($optionInfo['option_type'])) {
                try {
                    $group = $this->productOptionFactory->create()->groupFactory($optionInfo['option_type']);
                    return ['value' => $group->getCustomizedView($optionInfo)];
                } catch (\Exception $e) {
                    return $default;
                }
            }
            return $default;
        }

        // truncate standard view
        if (is_array($optionValue)) {
            $truncatedValue = implode("\n", $optionValue);
            $truncatedValue = nl2br($truncatedValue);
            return ['value' => $truncatedValue];
        }

        $truncatedValue = $this->filterManager->truncate($optionValue, ['length' => 55, 'etc' => '']);
        $truncatedValue = nl2br($truncatedValue);

        $result = ['value' => $truncatedValue];
        if ($this->stringUtils->strlen($optionValue) > 55) {
            $result['value'] .= ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>';
            $optionValue = nl2br($optionValue);
            $result = array_merge($result, ['full_view' => $optionValue]);
        }

        return $result;
    }

    /**
     * @param ProductSubscriptionProfileInterface $item
     * @return array
     */
    public function getItemOptions(ProductSubscriptionProfileInterface $item)
    {
        $result = [];
        $options = $item->getCustomOptions();
        if ($options) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }

            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }

            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }
        }

        return $result;
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

                $price = $this->getSubscriptionItemPrice($request, $profile, $updatedSubProduct);

                /** @var \Magento\Catalog\Model\Product $candidate */
                foreach ($candidates as $candidate) {
                    if ($candidate->getId() === $updatedSubProduct->getMagentoProductId()) {
                        //if $candidate is current updated product
                        $updatedSubProduct
                            ->setDataChanges(false)
                            ->setQty($candidate->getQty())
                            ->setCustomOptions($candidate->getTypeInstance()->getOrderOptions($candidate))
                            ->setPrice($price);
                        $profileChanged = $profileChanged || $updatedSubProduct->hasDataChanges();
                    } else {
                        //if $candidate is a configurable child product.
                        foreach ($profileProducts as $profileProduct) {
                            if ($profileProduct->getParentId() === $updatedSubProduct->getId()) {
                                $profileProduct->setMagentoProductId($candidate->getId())
                                    ->setSku($candidate->getSku())
                                    ->setName($candidate->getName())
                                    ->setCustomOptions($candidate->getTypeInstance()->getOrderOptions($candidate))
                                    ->setQty($candidate->getQty());
                                $profileChanged = $profileChanged || $profileProduct->hasDataChanges();
                                break;
                            }
                        }
                    }
                }
            }
            $profile->setDataChanges($profileChanged);
        }
        if (isset($request['item_' . $request['subscription_profile_id']]['period'])) {
            $periodValue = $request['item_' . $request['subscription_profile_id']]['period'];
            if ($periodValue <= 0 && $request['item_' . $request['subscription_profile_id']]['term'] == 0) {
                return strval(__('Bill times must be greater than 0.'));
            }
            $profile->setTotalBillingCycles($periodValue);
        }
        if (isset($request['item_' . $request['subscription_profile_id']]['billing_frequency'])) {
            $billingFrequency = $request['item_' . $request['subscription_profile_id']]['billing_frequency'];
            $profile->setBillingFrequencyId($billingFrequency);
        }
        if (isset($request['item_' . $request['subscription_profile_id']]['qty'])) {
            $products = $profile->getProducts();
            $request = $this->objectFactory->create($request);
            foreach ($products as $product) {
                $price = $this->getSubscriptionItemPrice($request, $profile, $product);
                $product->setPrice($price * $request['item_' . $request['subscription_profile_id']]['qty']);
            }
        }
        if (isset($request['item_' . $request['subscription_profile_id']]['start_on'])) {
            $startOn = $request['item_' . $request['subscription_profile_id']]['start_on'];
            $date = new \DateTime();
            $startDate = new \DateTime($startOn);
            $expression = 'PT' . $date->format('H') . 'H'
                . $date->format('i') . 'M'
                . $date->format('s') . 'S';
            $startDate->add(new \DateInterval($expression));
            $startOn = $startDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            $profile->setStartDate($startOn);
        }
        return $profile;
    }

    /**
     * Return calculated price for subscription item.
     *
     * @param DataObject $request
     * @param SubscriptionProfile $profile
     * @param $profileProduct
     * @return float|string
     */
    private function getSubscriptionItemPrice(
        DataObject $request,
        SubscriptionProfile $profile,
        $profileProduct
    ) {
        $magentoProduct = $profileProduct->getMagentoProduct();
        $requestData = $request->getData();
        $requestData['billing_frequency'] = $profile->getBillingFrequencyId();
        if (isset($requestData['price'])) {
            unset($requestData['price']);
        }
        return $this->configurableTypeManager
            ->setOriginalProfileProduct($profileProduct)
            ->getSubscriptionPrice($magentoProduct, $requestData);
    }
}
