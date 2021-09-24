<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Ui\Component\Form;
use Magento\Framework\UrlInterface;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use TNW\Subscriptions\Model\EmailNotifier;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Prepare orders history ui layout.
 */
class OrdersHistory extends BaseFormModifier
{
    /**#@+
     * Group name order history.
     */
    const GROUP_ORDER_HISTORY = 'order_history';
    /**#@-*/

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WebsiteRepositoryInterface
     */
    protected $websiteRepository;

    /**
     * OrdersHistory constructor.
     * @param UrlInterface $urlBuilder
     * @param Registry $registry
     * @param ScopeConfigInterface $scopeConfig
     * @param WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Registry $registry,
        ScopeConfigInterface $scopeConfig,
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->websiteRepository = $websiteRepository;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($urlBuilder, $registry);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta[static::GROUP_ORDER_HISTORY] = [
            'children' => [
                'order_history_listing' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => true,
                                'componentType' => 'insertListing',
                                'dataScope' => 'order_history_listing',
                                'externalProvider' => 'tnw_subscriptionprofile_edit_order_history_listing.'
                                    . 'tnw_subscriptionprofile_edit_order_history_listing_data_source',
                                'selectionsProvider' => 'tnw_subscriptionprofile_edit_order_history_listing.'
                                    . 'tnw_subscriptionprofile_edit_order_history_listing.'
                                    . 'tnw_subscriptionprofile_order_history_columns.ids',
                                'ns' => 'tnw_subscriptionprofile_edit_order_history_listing',
                                'render_url' => $this->getUrlBuilder()->getUrl('mui/index/render'),
                                'realTimeLink' => false,
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'profileId' => '${ $.provider }:data.subscription_profile_id',
                                    '__disableTmpl' => [
                                        'profileId' => false
                                    ]
                                ],
                                'exports' => [
                                    'profileId' => '${ $.externalProvider }:params.subscription_profile_id',
                                    '__disableTmpl' => [
                                        'profileId' => false
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'collapsible' => false,
                        'opened' => false,
                        'componentType' => Form\Fieldset::NAME,
                        'sortOrder' => 10
                    ],
                ],
            ],
        ];
        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $profile =  $this->getProfile();

        if ($profile && $profile->getId()) {
            $data[$profile->getId()]['subscription_profile_id'] = $profile->getId();
            $websiteId = $profile->getWebsiteId();
            try {
                $enableCommentAdd = $this->scopeConfig->getValue(
                    EmailNotifier::XML_PATH_ENABLE_COMMENT_ADDED,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $websiteId
                );
            } catch (\Exception $e) {
                $website = $this->websiteRepository->getById($websiteId);
                $enableCommentAdd = $this->scopeConfig->getValue(
                    EmailNotifier::XML_PATH_ENABLE_COMMENT_ADDED,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $website->getStoreId()
                );
            }
            $data[$profile->getId()]['disableCheckbox'] = $enableCommentAdd ? false : true;
        }

        return $data;
    }
}
