<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Controller\Cart;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\ItemFactory;
use Psr\Log\LoggerInterface;

/**
 * Configure product's options in subscription cart.
 */
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

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Context $context
     * @param ItemFactory $quoteItemFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param LoggerInterface $logger
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        ItemFactory $quoteItemFactory,
        CartRepositoryInterface $quoteRepository,
        LoggerInterface $logger,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->quoteRepository = $quoteRepository;
        $this->registry = $registry;
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
            $this->registry->register('old_quote_item_id', $quoteItemId);
        }

        try {
            if (!$quoteItem || $productId != $quoteItem->getProductId()) {
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
                    $quoteItem->getProductId(),
                    $this,
                    $params
                );

            return $resultPage;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We cannot configure the product.'));
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
}
