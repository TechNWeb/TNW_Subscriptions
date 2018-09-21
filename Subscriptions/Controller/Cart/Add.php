<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Cart;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Add product to existing quote, or to newly created one.
 */
class Add extends Action
{
    /**
     * @var CreateProfile
     */
    private $createProfile;

    /**
     * Requested product instance.
     *
     * @var ProductInterface
     */
    private $product;

    /**
     * Check request has form key and it's correct.
     *
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * Help retrieve product information from Db.
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Check module is enable.
     *
     * @var Config
     */
    private $config;

    /**
     * Help retrieve product information for correct store.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Subscription session.
     *
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @var ItemFactory
     */
    private $quoteItemFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var array
     */
    private $allowedRequestFields = [
        'product',
        'product_id',
        'qty',
        'subscribe_qty',
        'billing_frequency',
        'term',
        'period',
        'start_on',
        'selected_configurable_option',
        'super_attribute',
        'old_quote_item_id',
    ];

    /**
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param Validator $formKeyValidator
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param CreateProfile $createProfile
     * @param Config $config
     * @param ItemFactory $quoteItemFactory
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Context $context,
        QuoteSessionInterface $session,
        Validator $formKeyValidator,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        CreateProfile $createProfile,
        Config $config,
        ItemFactory $quoteItemFactory,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->createProfile = $createProfile;
        $this->config = $config;
        $this->session = $session;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context);
    }

    /**
     * Add product to subscription quote action.
     *
     * @inheritdoc
     */
    public function execute()
    {
        $message = __('We can\'t add this item to your subscription shopping cart right now.');
        $error = true;
        $redirectUrl = null;
        if ($this->config->isSubscriptionsActive()) {
            if ($this->formKeyValidator->validate($this->getRequest()) && $this->initProduct()) {
                $params = $this->getFilteredParams();
                try {
                    if (isset($params['old_quote_item_id'])) {
                        $quoteItemId = (int)$params['old_quote_item_id'];
                        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
                        $quoteItem = $this->quoteItemFactory->create()->load($quoteItemId);
                        $quote = $this->quoteRepository->get($quoteItem->getQuoteId());
                        $quoteItemToDelete = $quote->getItemById($quoteItem->getId());
                        $this->createProfile->removeSubscriptions($quoteItemToDelete);
                        $redirectUrl = $this->_url->getUrl('tnw_subscriptions/cart/index');
                    }
                    if (isset($params['subscribe_qty'])) {
                        $filter = new \Zend_Filter_LocalizedToNormalized(
                            ['locale' => $this->_objectManager->get(ResolverInterface::class)->getLocale()]
                        );
                        $params['qty'] = $filter->filter($params['subscribe_qty']);
                        unset($params['subscribe_qty']);
                    }
                    $result = $this->createProfile->addToSubscription($params);
                    if ($result) {
                        $message = __(
                            'You added %1 to your subscription cart.',
                            $this->initProduct()->getName()
                        );
                        $error = false;
                        $this->session->addSubQuote($result->getQuote());
                    }
                } catch (\Exception $e) {
                    $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                    $redirectUrl = null;
                }
            }
        }
        $this->processResponse($error, $message, $redirectUrl, $this->initProduct());

        return $this->_response;
    }

    /**
     * Check whether requested product exists in Db.
     *
     * @return ProductInterface|bool
     */
    private function initProduct()
    {
        if ($this->product === null) {
            $productId = (int)$this->getRequest()->getParam('product_id');
            $storeId = $this->storeManager->getStore()->getId();
            try {
                $this->product = $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                $this->product = false;
            }
        }

        return $this->product;
    }

    /**
     * Add necessary information to response.
     *
     * @param bool $error
     * @param string $message
     * @param string|null $redirectUrl
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    private function processResponse($error, $message, $redirectUrl, $product = null)
    {
        if ($message) {
            if ($error) {
                $this->messageManager->addErrorMessage($message);
            } else {
                $this->messageManager->addSuccessMessage($message);
            }
        }

        $result['error'] = $error;
        $result['message'] = $message;
        if ($redirectUrl) {
            $result['redirectUrl'] = $redirectUrl;
        }
        if ($product && !$product->getIsSalable()) {
            $result['product'] = [
                'statusText' => __('Out of stock'),
            ];
        }
        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );
    }

    /**
     * Returns filtered request params.
     *
     * @return array
     */
    private function getFilteredParams()
    {
        $allowed  = $this->allowedRequestFields;
        return array_filter(
            $this->getRequest()->getParams(),
            function ($key) use ($allowed) {
                return in_array($key, $allowed);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
