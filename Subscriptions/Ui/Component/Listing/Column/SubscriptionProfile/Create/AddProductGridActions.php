<?php

namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Create;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Form;

/**
 * Class CustomerGridActions
 * @package TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile
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
                    $label = ($item['type_id'] == 'configurable') ? '[' . __('Configure & Add') . ']' : __('[Add]');
                    $item[$this->getData('name')] = [
                        'view' => [
                            'label' => $label,
                            'callback' => [
                                'provider' => Form::DATA_SCOPE_MODAL_FORM . '.' . Form::DATA_SCOPE_MODAL_FORM,
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
