<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\BillingFrequency\Linked;

use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Ui\DataProvider\Product\Related\AbstractDataProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\Discount;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\LockPrice;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\UnlockPresetQty;
use \Magento\Framework\App\Response\RedirectInterface;

/**
 * Class LinkedDataProvider
 */
class LinkedDataProvider extends AbstractDataProvider
{
    /**
     * Was tables already joined to collection or not.
     *
     * @var bool
     */
    private $tablesJoined = false;

    /**
     * Redirect.
     *
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param ProductLinkRepositoryInterface $productLinkRepository
     * @param RedirectInterface $redirect
     * @param array $addFieldStrategies
     * @param array $addFilterStrategies
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        ProductRepositoryInterface $productRepository,
        StoreRepositoryInterface $storeRepository,
        ProductLinkRepositoryInterface $productLinkRepository,
        RedirectInterface $redirect,
        array $addFieldStrategies,
        array $addFilterStrategies,
        array $meta = [],
        array $data = []
    ) {
        $this->redirect = $redirect;

        parent::__construct($name,
            $primaryFieldName,
            $requestFieldName,
            $collectionFactory,
            $request,
            $productRepository,
            $storeRepository,
            $productLinkRepository,
            $addFieldStrategies,
            $addFilterStrategies,
            $meta,
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getLinkType()
    {
        return 'linked';
    }

    /**
     * @inheritdoc
     */
    public function getCollection()
    {
        $collection = parent::getCollection();
        $collection->addAttributeToFilter(
            'tnw_subscr_purchase_type',
            [
                'in' => [
                    PurchaseType::RECURRING_PURCHASE_TYPE,
                    PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE,
                ],
            ]
        );

        $collection->addAttributeToSelect([
            UnlockPresetQty::CODE_UNLOCK_PRESET_QTY,
            LockPrice::CODE_LOCK_PRICE,
            LockPrice::CODE_FLAT_DISCOUNT,
            Discount::CODE_DISCOUNT_AMOUNT,
            Discount::CODE_DISCOUNT_TYPE,
        ]);

        if (!$this->tablesJoined) {
            $this->joinTables($collection);

            $this->tablesJoined = true;
        }

        return $collection;
    }

    /**
     * Join table(s) to collection.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     */
    private function joinTables(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection)
    {
        $frequencyId = $this->getBillingFrequencyId();

        $alias = 'tnw_b_f';

        $collection->joinTable(
            [
                $alias => $collection->getTable(
                    ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE
                ),
            ],
            'magento_product_id=entity_id',
            [
                ProductBillingFrequencyInterface::INITIAL_FEE,
                ProductBillingFrequencyInterface::PRESET_QTY,
                ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID,
                'tnw_' . ProductBillingFrequencyInterface::PRICE => ProductBillingFrequencyInterface::PRICE,
            ],
            $alias . '.' . ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID . '=' .  $frequencyId,
            'left'
        );
    }

    /**
     * Get billing frequency id.
     *
     * @return null|string
     */
    private function getBillingFrequencyId()
    {
        $id = null;
        $url = $this->redirect->getRefererUrl();

        //get id from url
        if (preg_match('/\/id\/(?<id>\d+)(?:\/)?/', $url, $match)) {
            $id = $match['id'];
        }

        return $id;
    }
}
