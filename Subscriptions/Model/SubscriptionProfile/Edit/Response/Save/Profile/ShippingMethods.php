<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Edit\Response\Save\Profile;

use TNW\Subscriptions\Block\Subscription\Summary\Shipping\Details as ShippingDetailsBlock;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Shipping methods response processor.
 */
class ShippingMethods extends Base
{
    /**
     * @var ShippingDetailsBlock
     */
    private $shippingDetailsViewBlock;

    /**
     * @param ProfileManager $profileManager
     * @param ShippingDetailsBlock $shippingDetailsViewBlock
     */
    public function __construct(
        ProfileManager $profileManager,
        ShippingDetailsBlock $shippingDetailsViewBlock
    ) {
        parent::__construct($profileManager);
        $this->shippingDetailsViewBlock = $shippingDetailsViewBlock;

    }

    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $result = [];
        $engine = $this->profileManager->getShippingMethodFromRequestData($data);

        if ($engine) {
            $this->shippingDetailsViewBlock->addData([
                'subscription_profile' => $this->getSubscriptionProfile(),
                'only_table_content' => true,
            ]);
            $result['shipping_details'] = $this->shippingDetailsViewBlock->toHtml();
        }

        return $result;
    }
}
