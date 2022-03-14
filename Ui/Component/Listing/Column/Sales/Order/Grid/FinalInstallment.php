<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\Sales\Order\Grid;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Serialize\SerializerInterface;
use IntlDateFormatter;

/**
 * Class FinalInstallment - modifying final date if it exist
 */
class FinalInstallment extends Column
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * FinalInstallment constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TimezoneInterface $timezone
     * @param SerializerInterface $serializer
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        TimezoneInterface $timezone,
        SerializerInterface $serializer,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->timezone = $timezone;
        $this->serializer = $serializer;
    }

    /**
     * Add final installment to subscriptions profile page.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (!isset($item['subscription_final_installment_date'])
                || $item['subscription_final_installment_date'] == '') {
                    $item['subscription_final_installment_date'] = "";
                } else {
                    if (strpos($item['subscription_final_installment_date'], '[') !== false) {
                        $options = $this->serializer->unserialize($item['subscription_final_installment_date']);
                        $result = [];
                        foreach ($options as $option) {
                            if ($option === null) {
                                $result[] = " - ";
                            } else {
                                $result[] = $this->timezone->formatDate(
                                    $option,
                                    IntlDateFormatter::MEDIUM
                                );
                            }
                        }
                        $item['subscription_final_installment_date'] = implode(', ', $result);
                    } else {
                        $item['subscription_final_installment_date'] =
                            $this->timezone->formatDate(
                                $item['subscription_final_installment_date'],
                                IntlDateFormatter::MEDIUM
                            );
                    }
                }
            }
        }
        return $dataSource;
    }
}
