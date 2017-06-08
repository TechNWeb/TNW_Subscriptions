<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Backend\Block\Widget\Context;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;

abstract class GenericButton
{
    /** @var Context */
    protected $context;
    /** @var StepPool */
    protected $stepPool;

    /**
     * GenericButton constructor.
     * @param Context $context
     * @param StepPool $stepPool
     */
    public function __construct(
        Context $context,
        StepPool $stepPool
    )
    {
        $this->context = $context;
        $this->stepPool = $stepPool;
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
