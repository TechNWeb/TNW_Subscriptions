<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Billing;

use Magento\Customer\Block\Address\Edit;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Subscription billing address edit block
 */
class AddressEdit extends Edit
{
    /**
     * Registry model
     *
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var SubscriptionProfileAddressInterface
     */
    private $subscriptionAddress;

    /**
     * @var \TNW\Subscriptions\Model\Source\CustomerAddressProfileEdit
     */
    private $customerAddressSource;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Registry $registry
     * @param \TNW\Subscriptions\Model\Source\CustomerAddress $customerAddressSource
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Registry $registry,
        \TNW\Subscriptions\Model\Source\CustomerAddress $customerAddressSource,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->customerAddressSource = $customerAddressSource;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $customerSession,
            $addressRepository,
            $addressDataFactory,
            $currentCustomer,
            $dataObjectHelper,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($this->getSubscriptionProfile()) {
            $this->subscriptionAddress = $this->getSubscriptionProfile()->getBillingAddress();

            if ($this->subscriptionAddress) {
                $this->_address = $this->subscriptionAddress->exportCustomerAddress();
            }

        }

        return $this;
    }

    /**
     * Retrieve current subscription model instance.
     *
     * @return SubscriptionProfile
     */
    private function getSubscriptionProfile()
    {
        return $this->registry->registry('tnw_subscription_profile');
    }

    /**
     * Return customer addresses select
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerAddressesSelect()
    {
        $options = $this->customerAddressSource->toOptionArray();
        $html = $this->getLayout()
            ->createBlock(\Magento\Framework\View\Element\Html\Select::class)
            ->setName('billing_address[customer_billing_address_id]')
            ->setId('customer_address_id')
            ->setTitle(__('Customer Address'))
            ->setValue($this->getCustomerAddressId())
            ->setOptions($options)
            ->setExtraParams('data-validate="{\'validate-select\':true}"')
            ->getHtml();

        return $html;
    }

    /**
     * Retrieve Customer_address_id from current Subscription profile.
     *
     * @return int|string
     */
    private function getCustomerAddressId()
    {
        $customerAddressId = '';
        if ($this->subscriptionAddress) {
            $customerAddressId = $this->subscriptionAddress->getCustomerAddressId();
        }

        return $customerAddressId;
    }
}
