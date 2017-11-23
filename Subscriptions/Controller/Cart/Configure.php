<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Framework\Controller\ResultFactory;

class Configure extends \Magento\Framework\App\Action\Action
    implements \Magento\Catalog\Controller\Product\View\ViewInterface
{
    /**
     * @var ItemFactory
     */
    private $quoteItemFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    public function __construct(
        Context $context,
        ItemFactory $quoteItemFactory,
        CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($context);
        $this->quoteItemFactory = $quoteItemFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Action to reconfigure subscriptions cart item
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Extract item and product to configure
        $quoteItemId = (int)$this->getRequest()->getParam('id');
        $productId = (int)$this->getRequest()->getParam('product_id');
        $quoteItem = null;
        if ($quoteItemId) {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $quoteItem = $this->quoteItemFactory->create()->load($quoteItemId);
            $quote = $this->quoteRepository->get($quoteItem->getQuoteId());
            $quoteItem->setQuote($quote);
        }

        try {
            if (!$quoteItem || $productId != $quoteItem->getProduct()->getId()) {
                $this->messageManager->addError(__("We can't find the subscription item."));

                return $this->goBack();
            }

            $params = new \Magento\Framework\DataObject();
            $params->setCategoryId(false);
            $params->setConfigureMode(true);
            $params->setBuyRequest($quoteItem->getBuyRequest());

            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $this->_objectManager->get(\Magento\Catalog\Helper\Product\View::class)
                ->prepareAndRender(
                    $resultPage,
                    $quoteItem->getProduct()->getId(),
                    $this,
                    $params
                );

            return $resultPage;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We cannot configure the product.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);

            return $this->goBack();
        }
    }

    /**
     * Set back redirect url to response
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function goBack()
    {
        return $this->resultFactory
            ->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('tnw_subscriptions/cart/index');
    }
}
