<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Class SummaryShippingMethodForm
 */
class SummaryShippingMethodForm extends AbstractDataProvider
{
    /**
     * Form data scope.
     */
    const FORM_NAME = 'tnw_subscriptionprofile_summary_shipping_method_form';

    /**
     * Subscription profile
     *
     * @var SubscriptionProfile
     */
    private $profile;
    /**
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * SummaryForm constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ProfileManager $profileManager
     * @param array $meta
     * @param array $data
     * @internal param AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @internal param CustomerRepository $customerRepository
     * @internal param AddressRepository $addressRepository
     * @internal param AddressMapper $addressMapper
     * @internal param CustomerMapper $customerMapper
     * @internal param SubscriptionProfileRepositoryInterface $profileRepository
     * @internal param RequestInterface $request
     * @internal param $isShipping
     * @internal param Registry $registry
     * @internal param $isShipping
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ProfileManager $profileManager,
        array $meta = [],
        array $data = []
    ) {
        $this->profileManager = $profileManager;
        $this->profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data =  [];

        $data[SummaryInsertForm::FORM_DATA_KEY] = $this->getProfileId();

        return [
            $this->getProfileId() => $data
        ];
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        return $meta;
    }

    /**
     * Returns current subscription profile id from registry
     *
     * @return mixed|null|string
     */
    private function getProfileId()
    {
        return $this->profile ? $this->profile->getId() : null;
    }

}
