<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
        $arrItems = [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => [],
        ];
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
                ['relation' => $this->getCollection()->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)],
                'main_table.entity_id=relation.' . SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID,
                []
            )->join(
                ['shipping_address_table' => $this->getCollection()->getTable(self::MAGENTO_QUOTE_ADDRESS_TABLE)],
                'main_table.entity_id=shipping_address_table.' . self::MAGENTO_QUOTE_ADDRESS_CONDITION_ID
                . ' AND shipping_address_table.address_type = \'shipping\'',
                []
            )->join(
                ['billing_address_table' => $this->getCollection()->getTable(self::MAGENTO_QUOTE_ADDRESS_TABLE)],
                'main_table.entity_id=billing_address_table.' . self::MAGENTO_QUOTE_ADDRESS_CONDITION_ID
                . ' AND billing_address_table.address_type = \'billing\'',
                []
            )->where(
                'relation.' . SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID . ' is NULL'
            )->reset(
                \Zend_Db_Select::COLUMNS
            )->columns(
                $columns
            );


            $arrItems['totalRecords'] = $this->getCollection()->getSize();

            foreach ($this->getCollection()->getItems() as $item) {
                $arrItems['items'][] = $this->prepareItemData($item);
            }
        }

        return $arrItems;
    }

    /**
     * Preparing data from items.
     *
     * @param \Magento\Quote\Model\Quote $qoute
     * @return array
     */
    private function prepareItemData($qoute)
    {
        $quoteData = [];

        $currencyCode = isset($qoute['quote_currency_code'])
            ? $qoute['quote_currency_code']
            : null;
        $shippingPrice = $this->subContext->getPriceCurrency()->format(
            $qoute->getShippingAmount(),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $currencyCode
        );

        $grandTotal = $this->subContext->getPriceCurrency()->format(
            $qoute->getGrandTotal(),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            null,
            $currencyCode
        );

        $formatedEtityId = str_pad($qoute->getId(), 8, '0', STR_PAD_LEFT);

        $shippingDetails = __('N/A');
        if ($qoute->getShippingDetails()) {
            $shippingDetails = $qoute->getShippingDetails() . self::SHIPPING_DETAILS_SEPARATOR . $shippingPrice;
        }

        $quoteData['entity_id'] = $formatedEtityId;
        $quoteData['billing_name'] = $qoute->getBillingFirstname() . ' ' . $qoute->getBillingLastname();
        $quoteData['shipping_name'] = $qoute->getShippingFirstname() . ' ' . $qoute->getShippingLastname();
        $quoteData['scheduled_at'] = $qoute->getScheduledAt();
        $quoteData['shipping_details'] = $shippingDetails;
        $quoteData['scheduled_at'] = $qoute->getScheduledAt();
        $quoteData['grand_total'] = $grandTotal;

        return $quoteData;
    }

}
