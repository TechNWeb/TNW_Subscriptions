<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;

/**
 * Class CustomerGridActions
 */
class CustomerGridActions extends Column
{
    /**
     * Url builder.
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Step pool.
     *
     * @var StepPool
     */
    private $stepPool;

    /**
     * CustomerGridActions constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        StepPool $stepPool,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
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
            $step = $this->stepPool->getNextStep();

            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')]['continue'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'tnw_subscriptions/subscriptionprofile_create/process',
                        [
                            'customer_id' => $item['entity_id'],
                            StepPool::STEP_PARAM_NAME => $step
                        ]
                    ),
                    'label' => __('Select'),
                    'hidden' => false,
                ];
            }
        }

        return $dataSource;
    }
}
