<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Api\UrlBuilderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use \TNW\Subscriptions\Model\Config;

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
     * @var Config
     */
    private $config;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlBuilderInterface $profileUrlBuilder
     * @param Config $config
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlBuilderInterface $profileUrlBuilder,
        Config $config,
        array $components,
        array $data
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->profileUrlBuilder = $profileUrlBuilder;
        $this->config = $config;
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
                            'label' => $this->config->getPrefix($item[SubscriptionProfileInterface::STORE_ID])
                                . $item[SubscriptionProfileInterface::ID]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }

    /**
     * Redefinition method apply sorting
     *
     * @return void
     */
    protected function applySorting()
    {
        $sorting = $this->getContext()->getRequestParam('sorting');
        $isSortable = $this->getData('config/sortable');
        if ($isSortable !== false
            && !empty($sorting['field'])
            && !empty($sorting['direction'])
            && $sorting['field'] === $this->getName()
        ) {
            $this->getContext()->getDataProvider()->addOrder(
                'entity_id',
                strtoupper($sorting['direction'])
            );
        }
    }
}
