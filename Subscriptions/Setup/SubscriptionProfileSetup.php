<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Setup;

use Magento\Eav\Model\Entity\Setup\Context;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use \TNW\Subscriptions\Model\SubscriptionProfileFactory;

/**
 * Class Setup for subscription profile.
 */
class SubscriptionProfileSetup extends EavSetup
{
    /**
     * SubscriptionProfile model factory.
     *
     * @var SubscriptionProfileFactory
     */
    private $subscriptionProfileFactory;

    /**
     * @param ModuleDataSetupInterface $setup
     * @param Context $context
     * @param CacheInterface $cache
     * @param CollectionFactory $attrGroupCollectionFactory
     * @param SubscriptionProfileFactory $subscriptionProfileFactory
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        Context $context,
        CacheInterface $cache,
        CollectionFactory $attrGroupCollectionFactory,
        SubscriptionProfileFactory $subscriptionProfileFactory
    ) {
        $this->subscriptionProfileFactory = $subscriptionProfileFactory;

        parent::__construct($setup, $context, $cache, $attrGroupCollectionFactory);
    }

    /**
     * Creates SubscriptionProfile model.
     *
     * @param array $data
     *
     * @return \TNW\Subscriptions\Model\SubscriptionProfile
     *
     * @codeCoverageIgnore
     */
    public function createCategory($data = [])
    {
        return $this->subscriptionProfileFactory->create($data);
    }

    /**
     * Default entity and attributes.
     *
     * @return array
     */
    public function getDefaultEntities()
    {
        return [
            \TNW\Subscriptions\Model\SubscriptionProfile::ENTITY => [
                'entity_model' => \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile::class,
                'table' => SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                'attributes' => [
                    'customer_id' => [
                        'type' => 'static',
                        'label' => 'Customer Id',
                        'required' => false,
                        'sort_order' => 10,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                    'billing_frequency_id' => [
                        'type' => 'static',
                        'label' => 'Billing Frequency Id',
                        'required' => false,
                        'sort_order' => 20,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                    'label' => [
                        'type' => 'static',
                        'label' => 'Label',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                    'unit' => [
                        'type' => 'static',
                        'label' => 'Unit',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                    'website_id' => [
                        'type' => 'static',
                        'label' => 'Website Id',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                    'status' => [
                        'type' => 'static',
                        'label' => 'Status',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                    'frequency' => [
                        'type' => 'static',
                        'label' => 'Frequency',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                    'engine_code' => [
                        'type' => 'static',
                        'label' => 'Engine Code',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                    'shipping_address_id' => [
                        'type' => 'static',
                        'label' => 'Shipping Address Id',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                    'billing_address_id' => [
                        'type' => 'static',
                        'label' => 'Billing Address Id',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                    'created_at' => [
                        'type' => 'static',
                        'label' => 'Created At',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                    'updated_at' => [
                        'type' => 'static',
                        'label' => 'Updated At',
                        'required' => false,
                        'sort_order' => 30,
                        'visible' => true,
                        'group' => 'Additional information',
                    ],
                ],
            ],
        ];
    }
}
