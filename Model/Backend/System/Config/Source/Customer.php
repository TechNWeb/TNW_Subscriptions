<?php

namespace TNW\Subscriptions\Model\Backend\System\Config\Source;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;

class Customer implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options array
     */
    protected $options;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $groupCollectionFactory;

    /**
     * Customer constructor.
     * @param CollectionFactory $groupCollectionFactory
     */
    public function __construct(CollectionFactory $groupCollectionFactory)
    {
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->groupCollectionFactory->create()->loadData()->toOptionArray();
        }
        return $this->options;
    }
}
