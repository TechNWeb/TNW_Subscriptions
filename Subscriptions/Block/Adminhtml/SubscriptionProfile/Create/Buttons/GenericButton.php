<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Backend\Block\Widget\Context;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\Config;

/**
 * Base class for buttons.
 */
abstract class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var StepPool
     */
    protected $stepPool;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Context $context
     * @param StepPool $stepPool
     * @param Config $config
     */
    public function __construct(
        Context $context,
        StepPool $stepPool,
        Config $config
    ) {
        $this->context = $context;
        $this->stepPool = $stepPool;
        $this->config = $config;
    }

    /**
     * Return model ID
     *
     * @return int|null
     */
    public function getModelId()
    {
        return $this->context->getRequest()->getParam('id');
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
