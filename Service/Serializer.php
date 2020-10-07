<?php
/**
 * Copyright © TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Service;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Serializer - object used for serialization
 */
class Serializer
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Serializer constructor.
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param array $data
     * @return bool|string
     */
    public function serialize(array $data)
    {
        return $this->serializer->serialize($data);
    }

    /**
     * @param string $data
     * @return array|bool|float|int|string|null
     */
    public function unserialize(string $data)
    {
        return $this->serializer->unserialize($data);
    }
}
