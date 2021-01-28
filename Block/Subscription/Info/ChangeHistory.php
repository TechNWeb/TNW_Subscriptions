<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Info;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Pager;
use TNW\Subscriptions\Model\MessagePool;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory\Collection as MessagesCollection;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory\CollectionFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistory;

/**
 * Subscription change history block on customer account dashboard.
 */
class ChangeHistory extends ContentAbstract
{
    /**
     * @var MessagesCollection
     */
    private $messagesCollection;

    /**
     * @var CollectionFactory
     */
    private $messageHistoryCollectionFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param MessagePool $messagePool
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        MessagePool $messagePool,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->messageHistoryCollectionFactory = $collectionFactory;
        parent::__construct($context, $registry, $messagePool, $data);
    }

    /**
     * Retrieve current subscription messages collection.
     *
     * @return MessagesCollection
     */
    public function getChangeHistoryCollection()
    {
        if (!$this->messagesCollection) {
            /** @var MessagesCollection $collection */
            $collection = $this->messageHistoryCollectionFactory->create();
            $this->messagesCollection = $collection->getChangeHistoryCollection(
                $this->getSubscriptionProfile()->getId()
            )->addFieldToFilter('is_visible_on_front', 1);

            $page = ($this->getRequest()->getParam('p')) ? $this->getRequest()->getParam('p') : 1;
            $pageSize = ($this->getRequest()->getParam('limit')) ? $this->getRequest()->getParam('limit') : 10;
            $this->messagesCollection->setPageSize($pageSize);
            $this->messagesCollection->setCurPage($page);
            $this->messagesCollection->load();

            foreach ($this->messagesCollection as $message) {
                $this->prepareMessageHistoryData($message);
            }
        }
        return $this->messagesCollection;
    }

    /**
     * @param $messageHistoryData
     * @return array
     */
    private function prepareMessageHistoryData($messageHistoryData)
    {
        $convertedData = [];
        $convertedData['is_message_comment'] = $messageHistoryData['is_comment'] ? 1 : 0;
        $convertedData['comment_type'] = $messageHistoryData['is_comment'] ? __('Comment') : '';

        $convertedData['message'] = $messageHistoryData['is_comment']
            ? sprintf('"%s"', $messageHistoryData['message'])
            : $messageHistoryData['message'];

        $messageHistoryData->addData($convertedData);

        return $messageHistoryData;
    }

    /**
     * @return $this|ContentAbstract
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getChangeHistoryCollection()) {
            $pager = $this->getLayout()->createBlock(
                Pager::class,
                'subscription.change.history.pager'
            )->setCollection(
                $this->getChangeHistoryCollection()
            );
            $this->setChild('pager', $pager);
        }

        return $this;
    }

    /**
     * Return pager block html.
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Return message source.
     * If message object has customer_id: source => 'customer'.
     * If message object doesn't have customer_id: source => 'merchant'.
     *
     * @param MessageHistory $messageItem
     * @return string
     */
    public function getMessageSource(MessageHistory $messageItem)
    {
        $source = __('merchant');
        if ($messageItem->getCustomerId()) {
            $source = __('customer');
        }

        return $source;
    }

    /**
     * Return formatted message date/time.
     *
     * @param string $date
     * @return string
     */
    public function getFormattedDate($date)
    {
        $result = $this->_localeDate->formatDateTime(
            $date,
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::MEDIUM
        );

        return $result;
    }
}
