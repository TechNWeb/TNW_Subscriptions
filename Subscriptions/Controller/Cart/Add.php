<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Cart;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Config;
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
     * Add constructor.
     *
     * @param Session $checkoutSession
     * @param Context $context
     * @param Validator $formKeyValidator
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param CreateProfile $createProfile
     * @param Config $config
     */
    public function __construct(
        Session $checkoutSession,
        Context $context,
        Validator $formKeyValidator,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        CreateProfile $createProfile,
        Config $config
    ) {
        parent::__construct($context);
        $this->formKeyValidator = $formKeyValidator;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->createProfile = $createProfile;
        $this->config = $config;
    }

    /**
     * Add product to subscription quote action.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = __('We can\'t add this item to your subscription shopping cart right now.');
        $error = true;
        if ($this->config->isSubscriptionsActive()) {
            if ($this->formKeyValidator->validate($this->getRequest()) && $this->initProduct()) {
                $params = $this->getRequest()->getParams();
                try {
                    if (isset($params['subscribe_qty'])) {
                        $filter = new \Zend_Filter_LocalizedToNormalized(
                            ['locale' => $this->_objectManager->get(ResolverInterface::class)->getLocale()]
                        );
                        $params['qty'] = $filter->filter($params['subscribe_qty']);
                        unset($params['subscribe_qty']);
                    }
                    $response = $this->createProfile->addToSubscription($params);
                    $error = $response['error'];
                    if (!$error) {
                        $message = __(
                            'You added %1 to your subscription cart.',
                            $this->initProduct()->getName()
                        );
                    }
                } catch (\Exception $e) {
                    $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                }
            }
        }
        $this->processResponse($error, $message, $this->initProduct());

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
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    private function processResponse($error, $message, $product = null)
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
        if ($product && !$product->getIsSalable()) {
            $result['product'] = [
                'statusText' => __('Out of stock'),
            ];
        }
        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );
    }
}
