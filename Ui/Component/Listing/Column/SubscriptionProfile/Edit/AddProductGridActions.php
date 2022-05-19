<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Edit;

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * Class AddProductGridActions - ui component
 */
class AddProductGridActions extends Column
{
    /** @var UrlInterface */
    protected $urlBuilder;

    /**
     * CustomerGridActions constructor.
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
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['entity_id'])) {
                    $label = ($item['type_id'] == Configurable::TYPE_CODE || $item['type_id'] == Type::TYPE_BUNDLE)
                        ? '[' . __('Configure & Add') . ']'
                        : __('[Add]');
                    $item[$this->getData('name')] = [
                        'view' => [
                            'label' => $label,
                            'callback' => [
                                'provider' => 'tnw_subscriptionprofile_summary_add_product_modal_form.'
                                    . 'tnw_subscriptionprofile_summary_add_product_modal_form',
                                'target' => 'setProductId'
                            ]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
