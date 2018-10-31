<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Add product to existing quote, or to newly created one.
 */
class Add extends \Magento\Checkout\Controller\Cart
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $localeResolver;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );

        $this->productRepository = $productRepository;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|false
     */
    protected function initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Add product to shopping cart action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();

        try {
            if (isset($params['qty'])) {
                $params['qty'] = \Zend_Filter::filterStatic($params['qty'], 'LocalizedToNormalized', [
                    ['locale' => $this->localeResolver->getLocale()]
                ]);
            }

            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                return $this->goBack();
            }

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }

            $this->cart->save();

            $this->_eventManager->dispatch('checkout_cart_add_product_complete', [
                'product' => $product,
                'request' => $this->getRequest(),
                'response' => $this->getResponse()
            ]);

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if (!$this->cart->getQuote()->getHasError()) {
                    $message = __(
                        'You added %1 to your shopping cart.',
                        $product->getName()
                    );
                    $this->messageManager->addSuccessMessage($message);
                }

                return $this->goBack(null, $product);
            }

            return $this->resultRedirectFactory->create()->setPath('*/*');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage($e->getMessage());
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage($message);
                }
            }

            if (!$url = $this->_checkoutSession->getRedirectUrl(true)) {
                $url = $this->_redirect->getRedirectUrl($this->_url->getUrl('tnw_subscriptions/cart'));
            }

            return $this->goBack($url);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t add this item to your shopping cart right now.'));
            return $this->goBack();
        }
    }

    /**
     * Resolve response
     *
     * @param string $backUrl
     * @param \Magento\Catalog\Model\Product $product
     * @return $this|\Magento\Framework\Controller\Result\Redirect
     */
    protected function goBack($backUrl = null, $product = null)
    {
        if (!$this->_request->isAjax()) {
            return parent::_goBack($backUrl);
        }

        $result = [];

        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $backUrl;
        } else if ($product && !$product->getIsSalable()) {
            $result['product'] = [
                'statusText' => __('Out of stock')
            ];
        }

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData($result);
    }

    /**
     * Get resolved back url
     *
     * @param null $defaultUrl
     *
     * @return mixed|null|string
     */
    protected function getBackUrl($defaultUrl = null)
    {
        $returnUrl = $this->_request->getParam('return_url');
        if ($returnUrl && $this->_isInternalUrl($returnUrl)) {
            $this->messageManager->getMessages()->clear();
            return $returnUrl;
        }

        $shouldRedirectToCart = $this->_scopeConfig->getValue(
            'checkout/cart/redirect_to_cart',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($shouldRedirectToCart || $this->_request->getParam('in_cart')) {
            if ($this->_request->getActionName() === 'add' && !$this->_request->getParam('in_cart')) {
                $this->_checkoutSession->setContinueShoppingUrl($this->_redirect->getRefererUrl());
            }

            return $this->_url->getUrl('tnw_subscriptions/cart');
        }

        return $defaultUrl;
    }
}
