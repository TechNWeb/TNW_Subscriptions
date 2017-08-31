<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Order\Upcoming;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\Context;

/**
 * Class Upcoming orders data provider
 */
class DataProvider extends AbstractDataProvider
{
    /**#@+
     * Quote addresses table name.
     */
    const MAGENTO_QUOTE_ADDRESS_TABLE = 'quote_address';
    /**#@-*/

    /**#@+
     * Subscription orders condition field.
     */
    const MAGENTO_QUOTE_ADDRESS_CONDITION_ID = 'quote_id';
    /**#@-*/

    /**#@+
     * Separator from shipping detail and shipping price.
     */
    const SHIPPING_DETAILS_SEPARATOR = ' - ';
    /**#@-*/

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Subscription context.
     *
     * @var Context
     */
    private $subContext;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param Context $context
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        Context $context,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->request = $request;
        $this->subContext = $context;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }


    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $profileId = $this->request->getParam('subscription_profile_id', 0);
        if ($profileId) {
            $columns = [
                'entity_id' => 'main_table.entity_id',
                'shipping_firstname' => 'shipping_address_table.firstname',
                'shipping_lastname' => 'shipping_address_table.lastname',
                'billing_firstname' => 'billing_address_table.firstname',
                'billing_lastname' => 'billing_address_table.lastname',
                'scheduled_at' => 'relation.scheduled_at',
                'shipping_details' => 'shipping_address_table.shipping_description',
                'shipping_amount' => 'shipping_address_table.shipping_amount',
                'grand_total' => 'main_table.grand_total',
                'quote_currency_code' => 'main_table.quote_currency_code'
            ];

            $this->getCollection()->addFieldToFilter('relation.subscription_profile_id', $profileId);
            $this->getCollection()->getSelect()->join(
                ['relation' => SubscriptionProfileOrderInterface::MAIN_TABLE],
                'main_table.entity_id=relation.' . SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID,
                []
            )->join(
                ['shipping_address_table' => self::MAGENTO_QUOTE_ADDRESS_TABLE],
                'main_table.entity_id=shipping_address_table.' . self::MAGENTO_QUOTE_ADDRESS_CONDITION_ID . ' AND shipping_address_table.address_type = \'shipping\'',
                []
            )->join(
                ['billing_address_table' => self::MAGENTO_QUOTE_ADDRESS_TABLE],
                'main_table.entity_id=billing_address_table.' . self::MAGENTO_QUOTE_ADDRESS_CONDITION_ID . ' AND billing_address_table.address_type = \'billing\'',
                []
            )->where(
                'relation.' . SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID . ' is NULL'
            )->reset(
                \Zend_Db_Select::COLUMNS
            )->columns(
                $columns
            );
        }

        $arrItems = [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => [],
        ];

        foreach ($this->getCollection()->getItems() as $item) {
            $arrItems['items'][] = $this->prepareItemData($item);
        }

        return $arrItems;
    }

    /**
     * Preparing data from items.
     *
     * @param $item \Magento\Quote\Model\Quote
     * @return array
     */
    private function prepareItemData($item)
    {
        $itemData = [];

        $currencyCode = isset($item['quote_currency_code'])
            ? $item['quote_currency_code']
            : null;
        $shippingPrice = $this->subContext->getPriceCurrency()->format(
            $item->getShippingAmount(),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $currencyCode
        );

        $grandTotal = $this->subContext->getPriceCurrency()->format(
            $item->getGrandTotal(),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $currencyCode
        );

        $formatedEtityId = str_pad($item->getId(), 8, '0', STR_PAD_LEFT);

        $itemData['entity_id'] = $formatedEtityId;
        $itemData['billing_name'] = $item->getBillingFirstname() . ' ' . $item->getBillingLastname();
        $itemData['shipping_name'] = $item->getShippingFirstname() . ' ' . $item->getShippingLastname();
        $itemData['scheduled_at'] = $item->getScheduledAt();
        $itemData['shipping_details'] = $item->getShippingDetails() . self::SHIPPING_DETAILS_SEPARATOR . $shippingPrice;
        $itemData['scheduled_at'] = $item->getScheduledAt();
        $itemData['grand_total'] = $grandTotal;

        return $itemData;
    }

}
