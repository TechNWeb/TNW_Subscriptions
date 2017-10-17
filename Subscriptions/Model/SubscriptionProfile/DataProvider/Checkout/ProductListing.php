<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout;

use Magento\Catalog\Helper\Image;
use Magento\Framework\Api\Filter;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Item;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\Source\ShippingMethods;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;

class ProductListing extends AbstractDataProvider
{
    /**
     * Form scope and group values
     */
    const DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS_COLUMNS = 'tnw_subscriptionprofile_product_columns';

    /**
     * Subscription listing data scope
     */
    const DATA_SCOPE_SUBSCRIPTION_LISTING = 'tnw_subscriptionprofile_create_product_listing';

    /**#@+
     * Constants for subscription profile checkout steps
     */
    const CHECKOUT_STEP_SHIPPING = 'shipping';
    const CHECKOUT_STEP_BILLING = 'billing';
    const CHECKOUT_STEP_PAYMENT = 'payment';
    /**#@-*/

    private $scopeName;

    /**
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var ShippingMethods
     */
    private $shippingMethods;

    /**
     * @var DescriptionCreator
     */
    private $frequencyDescriptionCreator;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * Product constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param QuoteSessionInterface $session
     * @param Image $imageHelper
     * @param Context $context
     * @param ShippingMethods $shippingMethods
     * @param DescriptionCreator $frequencyDescriptionCreator
     * @param array $meta
     * @param array $data
     * @param string $scopeName
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        QuoteSessionInterface $session,
        Image $imageHelper,
        Context $context,
        ShippingMethods $shippingMethods,
        DescriptionCreator $frequencyDescriptionCreator,
        \Magento\Framework\App\Request\Http $request,
        array $meta = [],
        array $data = [],
        $scopeName = ''
    ) {
        $this->session = $session;
        $this->imageHelper = $imageHelper;
        $this->context = $context;
        $this->shippingMethods = $shippingMethods;
        $this->frequencyDescriptionCreator = $frequencyDescriptionCreator;
        $this->scopeName = $scopeName ? $scopeName : self::DATA_SCOPE_SUBSCRIPTION_LISTING . '.' . self::DATA_SCOPE_SUBSCRIPTION_LISTING;
        $this->request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta,
            $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $items = $products = [];
        $estimatedPayment = 0;
        $subQuotes = $this->session->getSubQuotes();
        $counter = 1;
        /** @var ModelQuote $subQuote */
        foreach ($subQuotes as $subQuote) {
            $fullSubscriptionData = null;
            $initialFee = 0;

            if (empty($subQuote->getAllItems())) {
                continue;
            }

            /** @var Item $item */
            foreach ($subQuote->getAllItems() as $item) {
                $nonUniqueData = $item->getBuyRequest()->getDataByPath(
                    Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME . DIRECTORY_SEPARATOR . Create::NON_UNIQUE
                );
                if (!$fullSubscriptionData) {
                    $fullSubscriptionData = $item->getBuyRequest()->getDataByPath(
                        Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME
                    );
                    $fullSubscriptionData[Create::NON_UNIQUE]['price'] = 0;
                }
                $fullSubscriptionData[Create::NON_UNIQUE]['price'] +=
                    isset($nonUniqueData['price']) ? $nonUniqueData['price'] * $item->getQty(): 0;

                $imageHelper = $this->imageHelper->init(
                    $item->getProduct(),
                    'product_thumbnail_image'
                );
                $products[] = [
                    'thumbnail_alt' => $imageHelper->getLabel(),
                    'thumbnail_src' => $imageHelper->getUrl(),
                    'qty' => '(x' . $item->getQty() . ')',
                    'name' => $item->getName(),
                    'conf_options' => [], //TODO add here configurable options
                ];

                $initialFee += $this->getItemInitialFee($item);
            }
            $subTotal = $subQuote->getGrandTotal();
            $estimatedPayment += (double)$subTotal;
            $items[] = [
                'title' => __('Subscription') . ' #' . $counter++,
                'products' => $products,
                'frequency_description' => $this->frequencyDescriptionCreator->getDescription(
                    $subQuote,
                    $fullSubscriptionData,
                    $initialFee
                ),
                'shipping_method' => $this->getShippingMethodData($subQuote),
            ];
            $products = [];
        }
        $estimatedPayment = $this->formatPrice($estimatedPayment);

        return [
            'totalRecords' => count($items),
            'items' => $items,
            'estimatedPayment' => $estimatedPayment
        ];
    }

    /**
     * @param $price
     * @return float
     */
    protected function formatPrice($price)
    {
        return $this->context->getPriceCurrency()->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->session->getStoreId(),
            $this->session->getCurrencyId()
        );
    }

    /**
     * @param ModelQuote $quote
     * @return array
     */
    protected function getShippingMethodData($quote)
    {
        $shippingMethods = [];
        $label = '';
        $needShowAttention = false;
        $this->shippingMethods->setQuote($quote);
        if ($this->shippingMethods->canShowShippingMethodLabel()) {
            $label = __('Selected on next step');
            $currentStep = $this->getCurrentCheckoutStep();
            if ($currentStep === self::CHECKOUT_STEP_PAYMENT) {
                $label = $this->shippingMethods->getCurrentMethodLabel();
                $currentShippingMethod = explode("_", $this->shippingMethods->getCurrentShippingMethod());
                if (!in_array($currentShippingMethod[0], $this->shippingMethods->getDontCostDependedMethodsCodes())) {
                    $needShowAttention = true;
                }
            } elseif ($currentStep === self::CHECKOUT_STEP_BILLING) {
                $shippingMethods = $this->shippingMethods->getShippingMethodsAsOptionArray();
                $needShowAttention = true;
                $label = '';
            }
        }
        return [
            'label' => $label,
            'methods' => $shippingMethods,
            'needShowAttention' => $needShowAttention,
            'sub_quote_id' => $quote->getId(),
            'value' => $quote->getShippingAddress()->getShippingMethod()
        ];
    }

    private function getCurrentCheckoutStep()
    {
        $result= '';

        $handle = $this->request->getParam('handle');
        if ($handle) {
            $handleArray = explode('_', $handle);
            $result = end($handleArray);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        $meta = array_merge_recursive(
            $meta,
            $this->getProductColumnsData()
        );

        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {

    }


    /**
     * Returns meta data for product columns.
     *
     * @return array
     */
    private function getProductColumnsData()
    {
        return [
            self::DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS_COLUMNS => [
                'children' => [
                    'shipping_method' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dependsCodes' => $this->shippingMethods->getDontCostDependedMethodsCodes(),
                                    'attentionMessage' => $this->shippingMethods->getShippingAttentionMessage()
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns quote item initial fee.
     *
     * @param Item $item
     * @return float
     */
    private function getItemInitialFee(Item $item)
    {
        $result = 0;
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees){
            $result = $initialFees->getSubsInitialFee();
        }

        return $result;
    }

}
