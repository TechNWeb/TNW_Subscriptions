<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Change\History;

use Magento\FrameWork\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory\Collection;
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
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var SubscriptionProfileInterface
     */
    private $profile;

    /**
     * @param TimezoneInterface $timezone
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param array $name
     * @param $primaryFieldName
     * @param $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        TimezoneInterface $timezone,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        SubscriptionProfileRepositoryInterface $profileRepository,
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
        $this->profileRepository = $profileRepository;
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
            $this->setProfile($this->profileRepository->getById($profileId));
            /** @var Collection $changeHistoryCollection */
            $changeHistoryCollection = $this->collection->getChangeHistoryCollection($profileId);
            $arrItems['totalRecords'] = $this->getCollection()->getSize();
            /** @var \TNW\Subscriptions\Model\SubscriptionProfile\MessageHistory $item */
            foreach ($changeHistoryCollection as $item) {
                $item->setMessage($item->formatMessage());
                $arrItems['items'][] = $this->getConvertMessageHistoryData($item->toArray());
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
            ? sprintf('"%s"', $messageHistoryData['message'])
            : $messageHistoryData['message'];
        $convertedData['author'] = $this->getProfileChangeAuthor($messageHistoryData);;
        // date format like "August 23rd, 2017   2:04:15 PM"
        $convertedData['date'] = $this->timezone->formatDateTime(
            $messageHistoryData['created_at'],
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::MEDIUM
        );

        return $convertedData;
    }

    /**
     * Returns subscription profile.
     *
     * @return SubscriptionProfileInterface
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Sets subscription profile.
     *
     * @param SubscriptionProfileInterface $profile
     */
    public function setProfile(SubscriptionProfileInterface $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Returns profile formatted changing author.
     *
     * @param $messageHistoryData
     * @return \Magento\Framework\Phrase|string
     */
    private function getProfileChangeAuthor($messageHistoryData)
    {
        if ($messageHistoryData['lastname']) {
            $author = sprintf(
                'By %s %s (%s)',
                $messageHistoryData['firstname'],
                $messageHistoryData['lastname'],
                $messageHistoryData['email']
            );
        } elseif ($messageHistoryData['customer_id'] && $this->getProfile()
            && $this->getProfile()->getCustomerId() === $messageHistoryData['customer_id']
        ) {
            $author = sprintf(
                'By %s %s (%s)',
                $this->getProfile()->getCustomer()->getFirstname(),
                $this->getProfile()->getCustomer()->getLastname(),
                $this->getProfile()->getCustomer()->getEmail()
            );
        } else {
            $author = __('By automated process');
        }

        return $author;
    }
}
