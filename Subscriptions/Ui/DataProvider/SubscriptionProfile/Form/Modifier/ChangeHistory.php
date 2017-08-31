<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory\CollectionFactory;

/**
 * Data provider for change history.
 */
class ChangeHistory implements ModifierInterface
{
    /**
     * Group name.
     */
    const GROUP_CHANGE_HISTORY = 'change_history';

    /**
     * Registry.
     *
     * @var Registry
     */
    private $registry;

    /**
     * Url interface.
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Collection Factory.
     *
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param Registry $registry
     * @param UrlInterface $url
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Registry $registry,
        UrlInterface $url,
        CollectionFactory $collectionFactory
    ) {
        $this->registry = $registry;
        $this->urlBuilder = $url;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $data = $this->getConvertedMessageHistoryData();

        $meta[static::GROUP_CHANGE_HISTORY] = [
            'children' => [
                static::GROUP_CHANGE_HISTORY . '_listing' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => true,
                                'componentType' => Container::NAME,
                                'template' => 'TNW_Subscriptions/form/subscription-profile/message-history',
                                'component' => 'TNW_Subscriptions/js/form/subscription-profile/message-history',
                                'imports' =>
                                    [
                                        'messageHistoryData' => $data
                                    ]
                            ],
                        ],
                    ],
                ],
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Subscription Change History'),
                        'collapsible' => true,
                        'opened' => true,
                        'componentType' => Form\Fieldset::NAME,
                        'sortOrder' => 10,
                    ],
                ],
            ],
        ];

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $profile = $this->registry->registry('tnw_subscription_profile');

        if ($profile && $profile->getId()) {
            $data[$profile->getId()]['subscription_profile_id'] = $profile->getId();
        }

        return $data;
    }

    /**
     * Get message history for subscription profile from registry.
     *
     * @return array
     */
    private function getConvertedMessageHistoryData()
    {
        $profile = $this->registry->registry('tnw_subscription_profile');

        $collection = $this->collectionFactory->create();
        $data = $collection->getDataForChangeHistory($profile->getId());

        $convertedData = [];
        foreach ($data as $key => $item) {
            $convertedData[$key]['author_name'] = $item['lastname']
                ? 'by ' . $item['firstname'] . ' ' . $item['lastname']
                : __('by automated process');
            $convertedData[$key]['author_email'] = $item['email'];
            $convertedData[$key]['message'] = $item['message'];
            $convertedData[$key]['is_comment'] = $item['is_comment'];
            $convertedData[$key]['comment_label'] = $item['is_comment'] ? __('Comment') : '';

            // date format like "August 23rd, 2017   2:04:15 PM"
            $convertedData[$key]['date'] = date('F dS, Y   g:i:s A', strtotime($item['created_at']));
        }

        return $convertedData;
    }
}
