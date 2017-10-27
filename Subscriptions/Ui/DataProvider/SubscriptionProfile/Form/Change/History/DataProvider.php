<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Change\History;

use Magento\Framework\Stdlib\DateTime;
use Magento\FrameWork\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory\CollectionFactory;

/**
 * Class DataProvider
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * DataProvider constructor.
     * @param TimezoneInterface $timezone
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        TimezoneInterface $timezone,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->timezone = $timezone;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $arrItems = [
            'items' => [],
            'totalRecords' => 0,
        ];
        $profileId = $this->request->getParam('subscription_profile_id', 0);
        if ($profileId) {
            $changeHistoryCollection = $this->collection->getChangeHistoryCollection($profileId);
            $arrItems['totalRecords'] = $this->getCollection()->getSize();
            /** @var \TNW\Subscriptions\Model\SubscriptionProfile\MessageHistory $item */
            foreach ($changeHistoryCollection as $item) {
                $item->setMessage($item->formatMessage());
                $arrItems['items'][]
                    = $this->getConvertMessageHistoryData($item->toArray([]));
            }
        }

        return $arrItems;
    }

    /**
     * Convert change history data for grid
     *
     * @param $messageHistoryData array
     * @return array
     */
    private function getConvertMessageHistoryData($messageHistoryData)
    {
        $convertedData = [];
        $convertedData['entity_id'] = $messageHistoryData['entity_id'];
        $convertedData['is_message_comment'] = $messageHistoryData['is_comment'] ? 1 : 0;
        $convertedData['comment_type'] = $messageHistoryData['is_comment'] ? __('Comment') : '';
        $convertedData['message'] = $messageHistoryData['is_comment']
            ? sprintf('"%s"', $messageHistoryData['message']) : $messageHistoryData['message'];
        $convertedData['author'] = $messageHistoryData['lastname'] ?
            sprintf('By %s %s (%s)', $messageHistoryData['firstname'], $messageHistoryData['lastname'], $messageHistoryData['email'])
            : __('By automated process');
        // date format like "August 23rd, 2017   2:04:15 PM"
        $convertedData['date'] = $this->timezone->formatDateTime(
            $messageHistoryData['created_at'],
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::MEDIUM
        );

        return $convertedData;
    }
}