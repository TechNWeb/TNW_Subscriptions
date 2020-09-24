<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Queue;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;
use TNW\Subscriptions\Model\Queue;
use TNW\Subscriptions\Model\ResourceModel\Queue\CollectionFactory;

/**
 * Class MassDelete
 */
class MassDelete extends Action
{
    /**
     * Factory fore retrieving queue collection.
     *
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Filter.
     *
     * @var Filter
     */
    private $filter;

    /**
     * MassDelete constructor.
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Filter $filter
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        Filter $filter
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->filter = $filter;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection(
            $this->collectionFactory->create()
        );
        $itemDeleted = 0;
        /** @var Queue $item */
        foreach ($collection->getItems() as $item) {
            $item->delete();
            $itemDeleted++;
        }
        $this->messageManager->addSuccessMessage(
            'A total of ' . $itemDeleted . ' record(s) have been deleted.',
            'backend'
        );

        return $this->resultRedirectFactory
            ->create()
            ->setPath($this->_redirect->getRefererUrl());
    }
}
