<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Api\UrlBuilderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Class Label Column
 */
class Label extends Column
{
    /**
     * @var UrlBuilderInterface
     */
    private $profileUrlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlBuilderInterface $profileUrlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlBuilderInterface $profileUrlBuilder,
        array $components,
        array $data
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->profileUrlBuilder = $profileUrlBuilder;
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
                if (isset($item[SubscriptionProfileInterface::ID])) {
                    $item[SubscriptionProfileInterface::LABEL] = [
                        'edit' => [
                            'href' => $this->profileUrlBuilder->getEditUrl($item[SubscriptionProfileInterface::ID]),
                            'label' => $item[SubscriptionProfileInterface::LABEL],
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
