<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Customer\Account;

use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryPaymentMethodForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier\PaymentModifierInterface;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Payment methods form on customer account dashboard
 */
class PaymentMethodForm extends SummaryPaymentMethodForm
{
    /**
     * Form data scope.
     */
    const FORM_NAME = 'tnw_subscriptionprofile_account_payment_method_form';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ProfileManager $profileManager
     * @param PoolInterface $modifiersPool
     * @param UrlInterface $urlBuilder
     * @param string $requestProfileIdField
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ProfileManager $profileManager,
        PoolInterface $modifiersPool,
        UrlInterface $urlBuilder,
        $requestProfileIdField = SummaryInsertForm::FORM_DATA_KEY,
        array $meta = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $profileManager,
            $modifiersPool,
            $requestProfileIdField,
            $meta,
            $data
        );
    }

    /**
     * @return array|mixed
     */
    public function getConfigData()
    {
        $configData = parent::getConfigData();

        $configData['submit_url'] = $this->urlBuilder->getUrl(
            '*/subscription_customer_account/save'
        );

        $configData['process_url'] = $this->urlBuilder->getUrl(
            '*/subscriptionprofile_create/process'
        );

        return $configData;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $poolMeta = [];
        foreach ($this->modifiersPool->getModifiersInstances() as $modifier) {
            if (method_exists($modifier, 'setPaymentFormName')) {
                $modifier->setPaymentFormName($this::FORM_NAME);
            }
            if (method_exists($modifier, 'setListens')) {
                $modifier->setListens([]);
            }
            if (method_exists($modifier, 'setProfileId')) {
                $modifier->setProfileId($this->getProfileId());
            }
            $poolMeta = $modifier->modifyMeta($poolMeta);
        }
        $meta = array_merge_recursive(
            [],
            $poolMeta
        );

        return $meta;
    }
}
