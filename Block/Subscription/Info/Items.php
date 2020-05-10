<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Info;

use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template\Context;
use TNW\Subscriptions\Block\Subscription\Info\Messages\ExpireWarningSupportInterface;
use TNW\Subscriptions\Model\MessagePool;

/**
 * Subscription items block on customer account dashboard.
 */
class Items extends ContentAbstract implements ExpireWarningSupportInterface
{
    /**
     * Items form layout handle.
     */
    const ITEMS_FORM_HANDLE = 'tnw_subscriptions_subscription_items_form';

    /**
     * @var string
     */
    protected $_template = 'subscription/items.phtml';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Items constructor.
     * @param Context $context
     * @param Registry $registry
     * @param MessagePool $messagePool
     * @param SerializerInterface $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        MessagePool $messagePool,
        SerializerInterface $serializer,
        array $data = []
    ) {
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $messagePool, $data);
    }

    /**
     * @return MessagePool
     */
    public function getMessagePool()
    {
        return $this->messagePool;
    }

    /**
     * @return bool
     */
    public function isSupported()
    {
        return true;
    }

    /**
     * Get JS layout
     *
     * @return string
     */
    public function getJsLayout()
    {
        $this->jsLayout['components'] = array_merge_recursive(
            $this->jsLayout['components'],
            $this->getConfig()
        );

        return $this->serializer->serialize($this->jsLayout);
    }

    /**
     * Returns layout config for block components.
     *
     * @return array
     */
    private function getConfig()
    {
        return [
            'products' => [
                'children' => [
                    'products_insert_form' => [
                        'update_url' => $this->getUrl('tnw_subscriptions/ui/render'),
                        'render_url' => $this->getRenderUrl(),
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns render url for insert form.
     *
     * @return string
     */
    private function getRenderUrl()
    {
        return $this->getUrl(
            'tnw_subscriptions/ui_render/handle',
            [
                'handle' => self::ITEMS_FORM_HANDLE,
                'entity_id' => $this->getSubscriptionProfile()->getId(),
            ]
        );
    }
}
