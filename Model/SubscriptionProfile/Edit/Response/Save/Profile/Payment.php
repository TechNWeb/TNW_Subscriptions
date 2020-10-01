<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Edit\Response\Save\Profile;

use TNW\Subscriptions\Block\Subscription\Summary\Payment\Details as PaymentDetailsBlock;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Payment method response processor.
 */
class Payment extends Base
{
    /**
     * @var PaymentDetailsBlock
     */
    private $paymentDetailsViewBlock;

    /**
     * @param ProfileManager $profileManager
     * @param PaymentDetailsBlock $paymentDetailsViewBlock
     */
    public function __construct(
        ProfileManager $profileManager,
        PaymentDetailsBlock $paymentDetailsViewBlock
    ) {
        parent::__construct($profileManager);
        $this->paymentDetailsViewBlock = $paymentDetailsViewBlock;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $result = [];
        $engine = $this->profileManager->getEngineFromRequestData($data);

        if ($engine) {
            $this->paymentDetailsViewBlock->addData([
                'subscription_profile' => $this->getSubscriptionProfile(),
                'only_table_content' => true,
            ]);

            $result['payment'] = $this->paymentDetailsViewBlock->toHtml();
        }

        return $result;
    }
}
