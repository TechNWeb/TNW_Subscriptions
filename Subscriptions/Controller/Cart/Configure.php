<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Cart;

use Magento\Catalog\Controller\Product\View\ViewInterface;
use Magento\Framework\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\ItemFactory;
use Psr\Log\LoggerInterface;

/**
 * Configure product's options in subscription cart.
 */
class Configure extends Action\Action implements ViewInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var \TNW\Subscriptions\Model\QuoteSessionInterface
     */
    private $quoteSession;

    /**
     * @param Action\Context $context
     * @param LoggerInterface $logger
     * @param Registry $registry
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession
     */
    public function __construct(
        Action\Context $context,
        LoggerInterface $logger,
        Registry $registry,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->registry = $registry;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->quoteSession = $quoteSession;
    }

    /**
     * Action to reconfigure subscriptions cart item
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // Extract item and product to configure
        $quoteItemId = (int)$this->getRequest()->getParam('id');
        $productId = (int)$this->getRequest()->getParam('product_id');
        $quoteItem = null;
        if ($quoteItemId) {
            $quoteItem = $this->initQuoteItem($quoteItemId);
        }

        try {
            if (!$quoteItem || $productId != $quoteItem->getProductId()) {
                $this->messageManager->addErrorMessage(__("We can't find the subscription item."));

                return $this->goBack();
            }

            $params = $this->dataObjectFactory->create()
                ->addData([
                    'category_id' => false,
                    'configure_mode' => true,
                    'buy_request' => $quoteItem->getBuyRequest(),
                ]);

            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $this->_objectManager->get(\Magento\Catalog\Helper\Product\View::class)
                ->prepareAndRender(
                    $resultPage,
                    $quoteItem->getProductId(),
                    $this,
                    $params
                );

            return $resultPage;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('We cannot configure the product.'));
            $this->logger->critical($e);

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

    /**
     * Init Quote item from itemId.
     *
     * @param int $quoteItemId
     * @return Item
     */
    private function initQuoteItem($quoteItemId)
    {
        foreach ($this->quoteSession->getSubQuotes() as $subQuote) {
            if (!$quoteItem = $subQuote->getItemById($quoteItemId)) {
                continue;
            }

            $this->registry->register('old_quote_item_id', $quoteItemId);
            return $quoteItem;
        }

        return null;
    }
}
