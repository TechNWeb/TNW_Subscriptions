<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\Sales\Order\Grid;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Api\UrlBuilderInterface;

/**
 * Order id column modifier.
 */
class ProfileLink extends Column
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
     * Add link to subscriptions profile page.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (!empty($item['profiles'])) {
                    $item['profiles'] = implode(', ', array_map(function ($profileId) {
                        return $this->profileUrlBuilder->getEditHtmlLink($profileId, true);
                    }, explode(',', $item['profiles'])));
                }
            }
        }

        return $dataSource;
    }
}
