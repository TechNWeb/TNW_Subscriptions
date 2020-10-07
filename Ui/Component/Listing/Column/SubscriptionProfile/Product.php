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
use TNW\Subscriptions\Service\Serializer;

/**
 * Class Product - ui component
 */
class Product extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * Product constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param Serializer $serializer
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        Serializer $serializer,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->serializer = $serializer;
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
                    $productId = $item['product_id'];
                    $productBuyRequest = !empty($item['product_options'])
                        ? $this->serializer->unserialize($item['product_options'])
                        : null;
                    $options = !empty($productBuyRequest['attributes_info'])
                        ? $productBuyRequest['attributes_info']
                        : null;
                    $item[$this->getData('name')] = [
                        'product' => [
                            'href' => $this->urlBuilder->getUrl('catalog/product/edit', ['id' => $productId]),
                            'label' => $item['product_name'],
                            'qty' => (float)$item['product_qty'],
                            'options' => $options
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
                'product_name',
                strtoupper($sorting['direction'])
            );
        }
    }
}
