<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Edit\Response\Save\Profile;

use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Block\Subscription\Summary\Address as AddressBlock;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Addresses response processor.
 */
class Address extends Base
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var AddressBlock
     */
    private $addressBlock;

    /**
     * @param ProfileManager $profileManager
     * @param AddressBlock $addressBlock
     * @param string $type
     */
    public function __construct(
        ProfileManager $profileManager,
        AddressBlock $addressBlock,
        $type
    ) {
        $this->addressBlock = $addressBlock;
        $this->type = $type;

        parent::__construct($profileManager);
    }

    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        if ($this->type === SubscriptionProfileAddressInterface::ADDRESS_TYPE_SHIPPING) {
            $keyAddress = 'shipping_address';
            $keyInfo = 'shipping_info';
        } else {
            $keyAddress = 'billing_address';
            $keyInfo = 'billing_info';
        }

        $result = [];

        if (!empty($data[$keyAddress]) && !empty($data[$keyInfo])) {
            $this->addressBlock->addData([
                'subscription_profile' => $this->getSubscriptionProfile(),
            ]);
            $this->addressBlock->setAddressType($this->type);
            $result[$keyAddress] = $this->addressBlock->getAddressHtml();
        }

        return $result;
    }
}
