<?php

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

class Summary extends AbstractDataProvider
{
    /**
     * @var PoolInterface
     */
    private $modifiersPool;

    /**
     * Summary constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param PoolInterface $modifiersPool
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        PoolInterface $modifiersPool,
        array $meta = [],
        array $data = []
    ) {
        $this->modifiersPool = $modifiersPool;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $data = [];
        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        return $data;
    }

    /**
     * @inheritdoc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
    }
}
