<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Cart;

use Magento\Framework\App\Action;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action\Action
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var \TNW\Subscriptions\Model\QuoteSessionInterface
     */
    private $quoteSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->formKeyValidator = $formKeyValidator;
        $this->quoteSession = $quoteSession;
        $this->logger = $logger;
    }

    /**
     * Delete shopping cart item action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->quoteByItem($id)->removeItem($id)->save();
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t remove the item.'));
                $this->logger->critical($e);
            }
        }

        return $this->resultRedirectFactory->create()
            ->setRefererUrl();
    }

    /**
     * Init Quote item from itemId.
     *
     * @param int $quoteItemId
     *
     * @return \Magento\Quote\Model\Quote
     * @throws LocalizedException
     */
    private function quoteByItem($quoteItemId)
    {
        foreach ($this->quoteSession->getSubQuotes() as $subQuote) {
            if (!$quoteItem = $subQuote->getItemById($quoteItemId)) {
                continue;
            }

            return $subQuote;
        }

        throw new LocalizedException(__("We can't find the subscription item."));
    }
}
