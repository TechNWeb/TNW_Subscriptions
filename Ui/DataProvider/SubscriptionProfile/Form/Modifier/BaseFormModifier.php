<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Base form modifier - Ui
 */
class BaseFormModifier implements ModifierInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * BaseFormModifier constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param Registry $registry
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Registry $registry
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Retrieve attention messages for tab.
     *
     * @return array
     */
    protected function getTabMessages()
    {
        return [];
    }

    /**
     * Returns current subscription profile from registry.
     *
     * @return SubscriptionProfile|null
     */
    protected function getProfile()
    {
        return $this->registry->registry('tnw_subscription_profile');
    }

    /**
     * Return url builder.
     *
     * @return UrlInterface
     */
    protected function getUrlBuilder()
    {
        return $this->urlBuilder;
    }
}
