<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Info;

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

        return \Zend_Json::encode($this->jsLayout);
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
                'entity_id' => $this->getSubscriptionProfile()->getId()
            ]
        );
    }
}
