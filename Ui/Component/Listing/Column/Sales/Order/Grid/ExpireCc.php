<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\Sales\Order\Grid;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class ExpireCc - modifying data about cc expiration to Yes or No format
 */
class ExpireCc extends Column
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * ExpireCc constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SerializerInterface $serializer
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SerializerInterface $serializer,
        array $components = [],
        array $data = []
    ) {
        $this->serializer = $serializer;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Add Expiration status to subscriptions profile page.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if ($item['subscription_expire_cc'] === null) {
                    $item['subscription_expire_cc'] = "";
                } else {
                    if (strpos($item['subscription_expire_cc'], '[') !== false) {
                        $options = $this->serializer->unserialize($item['subscription_expire_cc']);
                        $result = [];
                        foreach ($options as $option) {
                            if ($option === null) {
                                $result[] = "";
                            } else {
                                if ($option == 0) {
                                    $result[] = __("No");
                                } else {
                                    $result[] = __("Yes");
                                }
                            }
                        }
                        $item['subscription_expire_cc'] = implode(', ', $result);
                    } else {
                        if ($item['subscription_expire_cc'] == 0) {
                            $item['subscription_expire_cc'] = __("No");
                        } else {
                            $item['subscription_expire_cc'] = __("Yes");
                        }
                    }
                }
            }
            return $dataSource;
        }
    }
}
