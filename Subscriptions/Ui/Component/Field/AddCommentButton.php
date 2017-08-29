<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Field;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Form\Field;

/**
 * Add comment on Subscription dashboard.
 */
class AddCommentButton extends Field
{
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $url
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $url,
        $components = [],
        array $data = []
    ) {
        $this->url = $url;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        parent::prepare();

        $url = $this->url->getUrl('tnw_subscriptions/subscriptionprofile/addcomment');

        $import = [
            'imports' => [
                'url' => $url,
            ]
        ];

        $this->wrappedComponent->setData(
            'config',
            array_replace_recursive(
                $import,
                (array) $this->wrappedComponent->getData('config')
            )
        );

        $this->setData(
            'config',
            array_replace_recursive(
                $import,
                (array) $this->getData('config')
            )
        );
    }
}
