<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block;

use Magento\Framework\View\Element\Template;

class Checkout extends Template
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    private $formKey;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var \TNW\Subscriptions\Model\Checkout\CompositeConfigProvider
     */
    private $compositeConfigProvider;

    /**
     * @var \TNW\Subscriptions\Block\Checkout\LayoutProcessorInterface[]
     */
    private $layoutProcessors;

    public function __construct(
        Template\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \TNW\Subscriptions\Model\Checkout\CompositeConfigProvider $compositeConfigProvider,
        array $layoutProcessors = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->formKey = $formKey;
        $this->_isScopePrivate = true;
        $this->jsLayout = isset($data['jsLayout']) && \is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->serializer = $serializer;
        $this->compositeConfigProvider = $compositeConfigProvider;
        $this->layoutProcessors = $layoutProcessors;
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        foreach ($this->layoutProcessors as $processor) {
            $this->jsLayout = $processor->process($this->jsLayout);
        }

        return $this->serializer->serialize($this->jsLayout);
    }

    /**
     * Retrieve form key
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get subscriptions checkout configuration
     *
     * @return array
     */
    public function getCheckoutConfig()
    {
        return $this->compositeConfigProvider->getConfig();
    }

    /**
     * Get base url for block.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * @return bool|string
     */
    public function getSerializedCheckoutConfig()
    {
        return $this->serializer->serialize($this->getCheckoutConfig());
    }
}
