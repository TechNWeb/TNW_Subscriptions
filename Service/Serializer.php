<?php
/**
 * Copyright © TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Service;

use Magento\Framework\Serialize\SerializerInterface;

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

    public function serialize(array $data)
    {
        return $this->serializer->serialize($data);
    }

    public function unserialize(string $data)
    {
        return $this->serializer->unserialize($data);
    }
}
