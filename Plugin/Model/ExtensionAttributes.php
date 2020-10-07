<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Model;

/**
 * Class ExtensionAttributes - plugin to modify the data after extension attribute retrieval
 */
class ExtensionAttributes
{
    /**
     * @var \Magento\Framework\Api\ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * ExtensionAttributes constructor.
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionAttributesFactory
     */
    public function __construct(
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionAttributesFactory
    ) {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

    /**
     * @param \Magento\Framework\Model\AbstractExtensibleModel $subject
     * @param $return
     *
     * @return \Magento\Framework\Api\ExtensionAttributesInterface
     */
    public function afterGetExtensionAttributes(
        \Magento\Framework\Model\AbstractExtensibleModel $subject,
        $return
    ) {
        if ($return instanceof \Magento\Framework\Api\ExtensionAttributesInterface) {
            return $return;
        }

        /** @var \Magento\Framework\Api\ExtensionAttributesInterface $return */
        $return = $this->extensionAttributesFactory->create(\get_class($subject));

        $subject->setData($subject::EXTENSION_ATTRIBUTES_KEY, $return);
        return $return;
    }
}
