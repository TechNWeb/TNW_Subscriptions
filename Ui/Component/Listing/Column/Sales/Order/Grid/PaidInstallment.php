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
 * Class PaidInstallment - showing how many paid subscriptions
 * orders have been made and how many are left
 */
class PaidInstallment extends Column
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * PaidInstallment constructor.
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
     * Add paid installment to subscriptions profile page.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (!empty($item['subscription_paid_installment'])) {
                    if (strpos($item['subscription_paid_installment'], '[') !== false) {
                        $options = $this->serializer->unserialize($item['subscription_paid_installment']);
                        $result = [];
                        foreach ($options as $option) {
                            $option = explode(',', $option);
                            if ($this->isNotInfiniteSubscription(
                                $options,
                                isset($item['subscription_final_installment_date'])
                            )) {
                                $result[] = $option[0] . ' / ' . $option[1];
                            } else {
                                $result[] = $option[0] . ' / ∞';
                            }
                        }
                        $item['subscription_paid_installment'] = implode(', ', $result);
                    } else {
                        $options = explode(',', $item['subscription_paid_installment']);
                        if ($this->isNotInfiniteSubscription(
                            $options,
                            isset($item['subscription_final_installment_date'])
                        )) {
                            $item['subscription_paid_installment'] = $options[0] . ' / ' . $options[1];
                        } else {
                            $item['subscription_paid_installment'] = $options[0] . ' / ∞';
                        }
                    }
                }
            }
        }

        return $dataSource;
    }

    /**
     * @param $options
     * @param $final
     * @return bool
     */
    public function isNotInfiniteSubscription($options, $final)
    {
        return count($options) > 1
            && $options[1] != 0
            && $options[1] != -1
            && $options[1] != 1
            && $final;
    }
}
