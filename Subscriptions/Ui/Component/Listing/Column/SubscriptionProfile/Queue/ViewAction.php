<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Queue;

use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ViewAction extends Column
{
    /**
     * Url builder.
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Time zone interface
     *
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * ViewAction constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param TimezoneInterface $timeZoneInterface
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        TimezoneInterface $timeZoneInterface,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->timezone = $timeZoneInterface;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['id']) && $this->isNeedShowActions($item['scheduled_at'])) {
                    $item[$this->getData('name')] = [
                        'process' => [
                            'href' => $this->urlBuilder->getUrl(
                                'tnw_subscriptions/subscriptionprofile_queue/process',
                                [
                                    'id' => $item['id']
                                ]
                            ),
                            'label' => __('Process')
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                'tnw_subscriptions/subscriptionprofile_queue/delete',
                                [
                                    'id' => $item['id']
                                ]
                            ),
                            'label' => __('Delete')
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }

    /**
     * If scheduled date greater than date now try show actions.
     *
     * @param $scheduledAt string date in format 'Y-m-d H:i:s'.
     * @return bool
     */
    private function isNeedShowActions($scheduledAt)
    {
        $dateTime = new DateTime();
        $dateNow = $dateTime->strToTime($this->timezone->date()->format('Y-m-d H:i:s'));
        return $dateNow < $dateTime->strToTime($scheduledAt);
    }
}