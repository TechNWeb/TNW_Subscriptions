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
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Escaper;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Customer addresses data source.
 */
class CustomerAddress implements OptionSourceInterface
{
    /**
     * Admin session.
     *
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * Address helper.
     *
     * @var Address
     */
    private $addressHelper;

    /**
     * Repository for retrieving customer addresses.
     *
     * @var AddressRepositoryInterface
     */
    private $addressService;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * Filter builder.
     *
     * @var FilterBuilder
     */
    private $filterBuilder;


    /**
     * Address mapper.
     *
     * @var Mapper
     */
    private $addressMapper;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * CustomerAddress constructor.
     * @param QuoteSessionInterface $session
     * @param Address $addressHelper
     * @param AddressRepositoryInterface $addressService
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param Mapper $addressMapper
     * @param Escaper $escaper
     */
    public function __construct(
        QuoteSessionInterface $session,
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
    public function toOptionArray()
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
     * Returns list of customer addresses.
     *
     * @return AddressInterface[]
     */
    protected function getAddressCollection()
    {
        $result = [];
        if ($this->getCustomerId()) {
            $filter = $this->filterBuilder
                ->setField('parent_id')
                ->setValue($this->getCustomerId())
                ->setConditionType('eq')
                ->create();
            $this->criteriaBuilder->addFilters([$filter]);
            $searchCriteria = $this->criteriaBuilder->create();
            $result = $this->addressService->getList($searchCriteria)
                ->getItems();
        }

        return $result;
    }

    /**
     * Returns converted customer address.
     *
     * @param AddressInterface $address
     * @return string
     */
    private function getAddressAsString(AddressInterface $address)
    {
        $formatTypeRenderer = $this->addressHelper->getFormatTypeRenderer('oneline');
        $result = '';
        if ($formatTypeRenderer) {
            $result = $formatTypeRenderer->renderArray($this->addressMapper->toFlatArray($address));
        }

        return $this->escaper->escapeHtml($result);
    }

    /**
     * Returns customer id from session.
     *
     * @return int
     */
    private function getCustomerId()
    {
        return $this->session->getCustomerId();
    }
}
