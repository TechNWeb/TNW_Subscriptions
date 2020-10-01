<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Customer - ui component
 */
class Customer extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Customer constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare dataSource
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$this->getData('name')])) {
                    $customerId = $item[$this->getData('name')];
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl('customer/index/edit', ['id' => $customerId]),
                            'label' => $item['customer_name'],
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
                'customer_name',
                strtoupper($sorting['direction'])
            );
        }
    }
}
