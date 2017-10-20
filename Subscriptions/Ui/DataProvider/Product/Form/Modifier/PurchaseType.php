<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Fieldset;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;

/**
* Customize Available For field.
*/
class PurchaseType extends BaseModifier
{
    /**
     * Name of container for subscription attributes.
     *
     * @var string
     */
    protected $containerName = 'container_tnw_subscr_all';

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * Trial constructor.
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        StoreManagerInterface $storeManager,
        Context $context
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        parent::__construct($storeManager, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $purchaseTypePath = $this->arrayManager->findPath(
            Attribute::SUBSCRIPTION_PURCHASE_TYPE,
            $meta,
            null,
            'children'
        );
        $rootPath = $this->arrayManager->slicePath($purchaseTypePath, 0, 2);
        $rootArray = $this->arrayManager->get($rootPath, $meta);
        // Move all fields to container
        if (!empty($rootArray)) {
            $children = [];
            foreach ($rootArray as $key => $value) {
                if ($key != 'container_' . Attribute::SUBSCRIPTION_PURCHASE_TYPE) {
                    $children[$key] = $value;
                    $meta = $this->arrayManager->remove($rootPath . '/' . $key, $meta);
                }
            }

            $meta = $this->arrayManager->set(
                $rootPath. '/' . $this->containerName,
                $meta,
                [
                    'children' => $children,
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => null,
                                'formElement' => Fieldset::NAME,
                                'componentType' => Fieldset::NAME,
                                'breakLine' => false,
                                'component' => 'TNW_Subscriptions/js/components/purchase-type',
                                'imports' => [
                                    'changedPurchaseType' => 'index = ' . Attribute::SUBSCRIPTION_PURCHASE_TYPE . ':value',
                                    'changedTrialStatus' => 'index = ' . Attribute::SUBSCRIPTION_TRIAL_STATUS . ':checked',
                                    'changedOfferDiscount' => 'index = ' . Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT . ':checked',
                                    'changedLockPrice' => 'index = ' . Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE . ':checked',
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }
}
