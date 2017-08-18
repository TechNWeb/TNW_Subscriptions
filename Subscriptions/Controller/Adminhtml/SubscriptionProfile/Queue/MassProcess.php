<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Queue;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;
use TNW\Subscriptions\Model\Queue;
use TNW\Subscriptions\Model\ResourceModel\Queue\CollectionFactory;
use TNW\Subscriptions\Model\Source\Queue\Status;

/**
 * Class MassProcess
 * @package TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Queue
 */
class MassProcess extends Action
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
        /** @var Queue $item */
        foreach ($collection as $item) {
            $item->setAttemptCount(0);
            $item->setStatus(Status::QUEUE_STATUS_PENDING);
            $item->setMessage('');
        }
        $collection->save();
        $this->messageManager->addSuccessMessage(
            'A total of ' . count($collection) . ' record(s) have been prepared for re-process.',
            'backend'
        );

        return $this->resultRedirectFactory
            ->create()
            ->setPath($this->_redirect->getRefererUrl());
    }
}