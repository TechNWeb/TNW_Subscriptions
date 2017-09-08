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
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Framework\Data\OptionSourceInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

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
     * Profile state
     *
     * @var bool
     */
    private $profileState;

    /**
     * Profile repository
     *
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * Request
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * CustomerAddress constructor.
     * @param QuoteSessionInterface $session
     * @param Address $addressHelper
     * @param AddressRepositoryInterface $addressService
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param Mapper $addressMapper
     * @param Escaper $escaper
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param RequestInterface $request
     * @param $profileState
     */
    public function __construct(
        QuoteSessionInterface $session,
        Address $addressHelper,
        AddressRepositoryInterface $addressService,
        SearchCriteriaBuilder $criteriaBuilder,
        FilterBuilder $filterBuilder,
        Mapper $addressMapper,
        Escaper $escaper,
        SubscriptionProfileRepositoryInterface $profileRepository,
        RequestInterface $request,
        $profileState
    ) {
        $this->session = $session;
        $this->addressHelper = $addressHelper;
        $this->addressService = $addressService;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->addressMapper = $addressMapper;
        $this->escaper = $escaper;
        $this->profileState = $profileState;
        $this->profileRepository = $profileRepository;
        $this->request = $request;
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
     * Returns customer id.
     *
     * @return int
     */
    private function getCustomerId()
    {
        $customerId = null;
        switch ($this->profileState) {
            case SubscriptionProfile::STATE_EDIT :
                $customerId = $this->getCustomerIdFromProfile();
                break;
            case SubscriptionProfile::STATE_CREATE :
                $customerId = $this->getCustomerIdFromSession();
                break;
        }
        return $customerId;
    }

    /**
     * Returns customer id from session
     *
     * @return int
     */
    private function getCustomerIdFromSession()
    {
        return $this->session->getCustomerId();
    }

    /**
     * Returns customer id from profile
     *
     * @return mixed|null|string
     */
    private function getCustomerIdFromProfile()
    {
        $customerId = null;
        /** @var SubscriptionProfile $profile */
        $profile = $this->getProfile();
        if ($profile) {
            $customerId = $profile->getCustomerId();
        }
        return $customerId;
    }

    /**
     * Returns current subscription profile from request param.
     *
     * @return SubscriptionProfile|null
     */
    private function getProfile()
    {
        $profileId = (int)$this->request->getParam(SummaryInsertForm::FORM_DATA_KEY, 0);
        try {
            /** @var SubscriptionProfile $profile */
            $profile = $this->profileRepository->getById($profileId);
        } catch (\Exception $e) {
            $profile = null;
        }

        return $profile;
    }
}
