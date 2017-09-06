<?php
/**
 * Created by PhpStorm.
 * User: ivan
 * Date: 31.08.17
 * Time: 18:11
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Summary;

use Magento\Framework\Stdlib\DateTime;
use Magento\Backend\Block\Template;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile as SubscriptionProfileResource;

class AccountInformation extends Template
{
    const DATE_NOT_FOUND = 'N/A';

    /**
     * @var SubscriptionProfileInterface
     */
    private $subscriptionProfile;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;
    /**
     * @var ProfileStatus
     */
    private $profileStatus;
    /**
     * @var SubscriptionProfileResource
     */
    private $subscriptionProfileResource;

    /**
     * AccountInformation constructor.
     * @param SubscriptionProfileResource $subscriptionProfileResource
     * @param ProfileStatus $profileStatus
     * @param GroupRepositoryInterface $groupRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param Registry $registry
     * @param Template\Context $context
     * @param array $data
     * @internal param SubscriptionProfileResource $subscriptionProfile
     */
    public function __construct(
        SubscriptionProfileResource $subscriptionProfileResource,
        ProfileStatus $profileStatus,
        GroupRepositoryInterface $groupRepository,
        CustomerRepositoryInterface $customerRepository,
        Registry $registry,
        Template\Context $context,
        array $data = []

    ) {
        $this->setTemplate('TNW_Subscriptions::subscription_profile/summary/account_information.phtml');
        parent::__construct($context, $data);

        if ($this->subscriptionProfile === null) {
            $this->subscriptionProfile = $registry->registry('tnw_subscription_profile');
        }

        $this->customerRepository = $customerRepository;
        $this->groupRepository = $groupRepository;
        $this->profileStatus = $profileStatus;
        $this->subscriptionProfileResource = $subscriptionProfileResource;
    }

    /**
     * Return subscription status
     *
     * @return null|string
     */
    public function getStatus()
    {
        $status = $this->subscriptionProfile->getStatus();
        return $this->profileStatus->getLabelByValue($status);
    }

    /**
     * Return created at date by subscription
     *
     * @return string
     */
    public function getCreatedOn()
    {
        return $this->normalizeDateFormat($this->subscriptionProfile->getCreatedAt());
    }

    /**
     * Return last submit order data
     *
     * @return string
     */
    public function getLastSuccessfulOrder()
    {
        $date = '';
        $lastOrderData = $this->subscriptionProfileResource
            ->getLastOrderData($this->subscriptionProfile, true);
        if ($lastOrderData) {
            $date = $lastOrderData['scheduled_at'];
        }
        return $this->normalizeDateFormat($date);
    }

    /**
     * Return date end trial
     *
     * @return string
     */
    public function getTrialEndsOn()
    {
        $date = '';
        if ($this->subscriptionProfile->getTrialStartDate()) {
            $date = $this->subscriptionProfile->getStartDate();
        }
        return $this->normalizeDateFormat($date);
    }

    /**
     * If term equal 1 return 'N/A',
     * if term equal 0 return last not submit order date
     *
     * @return string
     */
    public function getSubscriptionEndsOn()
    {
        $result = self::DATE_NOT_FOUND;
        $term = $this->subscriptionProfile->getTerm();
        switch ($term) {
            case 0:
                $date = '';
                $lastOrderData = $this->subscriptionProfileResource
                    ->getLastOrderData($this->subscriptionProfile, false);
                if ($lastOrderData) {
                    $date = $lastOrderData['scheduled_at'];
                }
                $result = $this->normalizeDateFormat($date);
                break;
            case 1:
                break;
            default:
                break;
        }

        return $result;
    }

    /**
     * Return customer name
     *
     * @return string
     */
    public function getCustomerName()
    {
        try {
            $customerEmail = $this->getCustomer()->getFirstname();
        } catch (NoSuchEntityException $e) {
            $customerEmail = 'undefined';
        }

        return $customerEmail;
    }

    /**
     * Return customer email
     *
     * @return string
     */
    public function getEmail()
    {
        try {
            $customerEmail = $this->getCustomer()->getEmail();
        } catch (NoSuchEntityException $e) {
            $customerEmail = 'undefined';
        }

        return $customerEmail;
    }

    /**
     * Return customer group
     *
     * @return string
     */
    public function getCustomerGroup()
    {
        $groupCode = 'undefined';
        $groupId = $this->getCustomer()->getGroupId();

        if (is_numeric($groupId)) {
            try {
                /** @var \Magento\Customer\Model\Data\Group $group */
                $group = $this->groupRepository->getById($groupId);
                $groupCode = $group->getCode();
            } catch (NoSuchEntityException $e) {
                $groupCode = 'undefined';
            }
        }

        return $groupCode;
    }

    /**
     * Return subscription currency code
     *
     * @return null|string
     */
    public function getCurrency()
    {
        return $this->subscriptionProfile->getProfileCurrencyCode();
    }


    /**
     * Return subscription website name
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->subscriptionProfile->getWebsite()->getName();
    }

    /**
     * Return customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws NoSuchEntityException
     */
    private function getCustomer()
    {
        $customerId = $this->subscriptionProfile->getCustomerId();
        return $this->customerRepository->getById($customerId);
    }

    /**
     * Date to normalize format
     *
     * @param $date string
     * @return string
     */
    private function normalizeDateFormat($date)
    {
        $result = self::DATE_NOT_FOUND;
        if ($date) {
            $dateTime = new DateTime();
            $result = date('F dS, Y', $dateTime->strToTime($date));
        }
        return $result;
    }


}