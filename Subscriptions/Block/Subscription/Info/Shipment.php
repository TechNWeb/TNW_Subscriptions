<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Info;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Json\Encoder;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use TNW\Subscriptions\Block\Subscription\Info\Messages\ExpireWarningSupportInterface;
use TNW\Subscriptions\Model\MessagePool;

/**
 * Subscription shipment block at customer account dashboard.
 */
class Shipment extends ContentAbstract implements ExpireWarningSupportInterface
{
    /**
     * Filter builder.
     *
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var Encoder
     */
    private $jsonEncoder;

    /**
     * Repository for retrieving customer addresses.
     *
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param MessagePool $messagePool
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param AddressRepositoryInterface $addressRepository
     * @param Encoder $encoder
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        MessagePool $messagePool,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $criteriaBuilder,
        AddressRepositoryInterface $addressRepository,
        Encoder $encoder,
        array $data = []
    ) {
        $this->filterBuilder = $filterBuilder;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->addressRepository = $addressRepository;
        $this->jsonEncoder = $encoder;
        parent::__construct($context, $registry, $messagePool, $data);
    }

    /**
     * @return MessagePool
     */
    public function getMessagePool()
    {
        return $this->messagePool;
    }

    /**
     * @return bool
     */
    public function isSupported()
    {
        return true;
    }

    /**
     * Return shipping info block.
     *
     * @return string
     */
    public function getShippingInfoViewHtml()
    {
        return $this->getChildHtml('shipping-info-view');
    }

    /**
     * Return shipping address edit block.
     *
     * @return string
     */
    public function getShippingInfoEditHtml()
    {
        return $this->getChildHtml('customer_address_edit_form');
    }

    /**
     * @inheritdoc
     */
    protected function initChildBlock(\Magento\Framework\View\Element\AbstractBlock $block)
    {
        $block->addData([
            'subscription_profile' => $this->getSubscriptionProfile(),
        ]);

        return $block;
    }

    /**
     * @return string
     */
    public function getSaveAddressUrl()
    {
        return $this->_urlBuilder->getUrl(
            'tnw_subscriptions/subscription_customer_account/save',
            [
                'entity_id' => $this->getSubscriptionProfile()->getId(),
                'isAjax' => true,
            ]
        );
    }

    /**
     * Check if it necessary to show edit form.
     *
     * @return int
     */
    public function isShowEdit()
    {
        return (int)$this->_request->getParam('ship_address');
    }

    /**
     * Retrieve customer addresses data to display.
     *
     * @return string
     */
    public function getCustomerAddressesData()
    {
        $result = [];
        $customerId = $this->getSubscriptionProfile()->getCustomerId();

        $filter = $this->filterBuilder
            ->setField('parent_id')
            ->setValue($customerId)
            ->setConditionType('eq')
            ->create();
        $this->criteriaBuilder->addFilters([$filter]);
        $searchCriteria = $this->criteriaBuilder->create();
        $addressesList = $this->addressRepository->getList($searchCriteria)
            ->getItems();

        foreach ($addressesList as $address) {
            $streetData = '';
            if ($address->getStreet()) {
                foreach ($address->getStreet() as $key =>$streetValue) {
                    $streetKey = 'street_' . ($key+1);
                    $streetData[$streetKey] = $streetValue;
                }
            }

            $addressData = [
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'company' => $address->getCompany(),
                'telephone' => $address->getTelephone(),
                'city' => $address->getCity(),
                'region' => $address->getRegion()->getRegion(),
                'region_id' => $address->getRegionId(),
                'zip' => $address->getPostcode(),
                'fax' => $address->getFax(),
                'vat_id' => $address->getVatId(),
                'country' =>$address->getCountryId(),
            ];

            $result[$address->getId()] = array_merge($addressData, $streetData);

        }

        return str_replace('"', "'", $this->jsonEncoder->encode($result));
    }
}
