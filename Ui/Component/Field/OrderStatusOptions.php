<?php

namespace TNW\Subscriptions\Ui\Component\Field;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class OrderStatusOptions implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $orderStatusFactory;

    public function __construct(CollectionFactory $orderStatusFactory)
    {
        $this->orderStatusFactory = $orderStatusFactory;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return array_merge(
            [
                [
                    'label' => 'Any',
                    'value' => 'any'
                ]
            ],
            $this->orderStatusFactory->create()->toOptionArray()
        );
    }
}
