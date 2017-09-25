<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\CancelButtonPopup;

class CancelButton extends AbstractDataProvider
{
    const CANCEL_BUTTON_HANDLER = 'tnw_subscriptionprofile_cancel_button_popup';

    /**
     * Url Builder.
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlInterface $urlBuilder,
        array $meta = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = [];

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        $meta = array_merge_recursive(
            $meta,
            [
                'tnw_subscriptionprofile_cancel_button_popup' => [
                    'children' => [
                        'cancelModal' => $this->getCancelModal(),
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => '',
                                'collapsible' => false,
                                'componentType' => Fieldset::NAME,
                                'dataScope' => '',
                            ],
                        ],
                    ],
                ],
            ]
        );

        return $meta;
    }

    /**
     * Get cancel modal window.
     *
     * @return array
     */
    private function getCancelModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'component' => 'TNW_Subscriptions/js/modal/modal-component-cancel-button-popup',
                        'options' => [
                            'modalClass' => 'modal-popup tnw_subscriptionprofile_cancel_button_popup',
                        ]
                    ],
                ],
            ],
            'children' => [
                'tnw_subscriptionprofile_cancel_button_popup_form' => $this->getCancelButtonForm()
            ],
        ];
    }

    /**
     * Get form for cancel button popup.
     *
     * @return array
     */
    private function getCancelButtonForm()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => true,
                        'componentType' => Container::NAME,
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => self::CANCEL_BUTTON_HANDLER
                            ]
                        ),
                        'autoRender' => true,
                        'ns' => CancelButtonPopup::DATA_SCOPE_CANCEL_BUTTON_MODAL_FORM,
                        'externalProvider' => CancelButtonPopup::DATA_SCOPE_CANCEL_BUTTON_MODAL_FORM . '.' . CancelButtonPopup::DATA_SCOPE_CANCEL_BUTTON_MODAL_FORM
                            . '_data_source',
                    ],
                ],
            ]
        ];
    }
}
