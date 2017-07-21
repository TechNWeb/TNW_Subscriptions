<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Source;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Helper\Address;
use Magento\Customer\Model\Address\Mapper;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Escaper;
use TNW\Subscriptions\Model\Backend\Session\Quote;

/**
 * Trial length unit type attribute and configuration data source.
 */
class CustomerAddress extends AbstractSource
{
    /** @var Quote */
    private $session;
    /** @var Address */
    private $addressHelper;
    /** @var AddressRepositoryInterface */
    private $addressService;
    /** @var SearchCriteriaBuilder */
    private $criteriaBuilder;
    /** @var FilterBuilder */
    private $filterBuilder;
    /** @var Mapper */
    private $addressMapper;
    /** @var Escaper */
    private $escaper;

    /**
     * CustomerAddress constructor.
     * @param Quote $session
     * @param Address $addressHelper
     * @param AddressRepositoryInterface $addressService
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param Mapper $addressMapper
     * @param Escaper $escaper
     */
    public function __construct(
        Quote $session,
        Address $addressHelper,
        AddressRepositoryInterface $addressService,
        SearchCriteriaBuilder $criteriaBuilder,
        FilterBuilder $filterBuilder,
        Mapper $addressMapper,
        Escaper $escaper
    ) {
        $this->session = $session;
        $this->addressHelper = $addressHelper;
        $this->addressService = $addressService;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->addressMapper = $addressMapper;
        $this->escaper = $escaper;
    }


    /**
     * @return array
     */
    public function getAllOptions()
    {
        $optionList = [];

        foreach ($this->getAddressCollection() as $address) {
            $optionList[] = [
                'value' => $address->getId(),
                'label' => $this->getAddressAsString($address),
                'empty' => false
            ];
        }

        if (count($optionList) > 0) {
            $optionList[] = [
                'value' => 0,
                'label' => '',
                'empty' => true
            ];

        }

        return $optionList;
    }

    /**
     * @return AddressInterface[]
     */
    protected function getAddressCollection()
    {
        if ($this->getCustomerId()) {
            $filter = $this->filterBuilder
                ->setField('parent_id')
                ->setValue($this->getCustomerId())
                ->setConditionType('eq')
                ->create();
            $this->criteriaBuilder->addFilters([$filter]);
            $searchCriteria = $this->criteriaBuilder->create();
            $result = $this->addressService->getList($searchCriteria);
            return $result->getItems();
        }
        return [];
    }

    /**
     * @param AddressInterface $address
     * @return string
     */
    protected function getAddressAsString(AddressInterface $address)
    {
        $formatTypeRenderer = $this->addressHelper->getFormatTypeRenderer('oneline');
        $result = '';
        if ($formatTypeRenderer) {
            $result = $formatTypeRenderer->renderArray($this->addressMapper->toFlatArray($address));
        }

        return $this->escaper->escapeHtml($result);
    }

    protected function getCustomerId()
    {
        return $this->session->getCustomerId();
    }
}
