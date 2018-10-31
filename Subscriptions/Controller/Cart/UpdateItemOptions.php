<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Cart;

class UpdateItemOptions extends \Magento\Checkout\Controller\Cart
{
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

        $this->localeResolver = $localeResolver;
    }

    /**
     * Update product configuration for a cart item
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $params = $this->getRequest()->getParams();

        if (!isset($params['options'])) {
            $params['options'] = [];
        }

        try {
            if (isset($params['qty'])) {
                $params['qty'] = \Zend_Filter::filterStatic($params['qty'], 'LocalizedToNormalized', [
                    ['locale' => $this->localeResolver->getLocale()]
                ]);
            }

            $quoteItem = $this->cart->getQuote()->getItemById($id);
            if (!$quoteItem) {
                throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t find the quote item.'));
            }

            $item = $this->cart->updateItem($id, new \Magento\Framework\DataObject($params));
            if (\is_string($item)) {
                throw new \Magento\Framework\Exception\LocalizedException(__($item));
            }

            if ($item->getHasError()) {
                throw new \Magento\Framework\Exception\LocalizedException(__($item->getMessage()));
            }

            $related = $this->getRequest()->getParam('related_product');
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }

            $this->cart->save();

            $this->_eventManager->dispatch('checkout_cart_update_item_complete', [
                'item' => $item,
                'request' => $this->getRequest(),
                'response' => $this->getResponse()
            ]);

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if (!$this->cart->getQuote()->getHasError()) {
                    $message = __(
                        '%1 was updated in your shopping cart.',
                        $item->getProduct()->getName()
                    );
                    $this->messageManager->addSuccessMessage($message);
                }

                return $this->_goBack($this->_url->getUrl('tnw_subscriptions/cart'));
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

            if ($url = $this->_checkoutSession->getRedirectUrl(true)) {
                return $this->_goBack($url);
            }

            $cartUrl = $this->_url->getUrl('tnw_subscriptions/cart');
            return $this->_goBack($this->_redirect->getRedirectUrl($cartUrl));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t update the item right now.'));
            return $this->_goBack();
        }
    }
}
