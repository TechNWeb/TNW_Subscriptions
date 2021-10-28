<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Queue;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Model\Config;

/**
 * Class ProfileLink - ui
 */
class ProfileLink extends Column
{
    /**
     * @var Config
     */
    private $config;

    /**
     * ProfileLink constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Config $config
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Config $config,
        array $components = [],
        array $data = []
    ) {
        $this->config = $config;
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
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {

                if (isset($item[$fieldName])) {
                    $url = $this->context->getUrl(
                        'tnw_subscriptions/subscriptionprofile/edit/',
                        ['entity_id' => $item['subscription_profile_id']]
                    );
                    $html = sprintf(
                        "<a target=\"_blank\" href ='%s'\">%s</a>",
                        $url,
                        $this->config->getPrefix($item['store_id']) . $item[$fieldName]
                    );
                    $item[$fieldName] = $html;
                }
            }
        }

        return $dataSource;
    }
}
