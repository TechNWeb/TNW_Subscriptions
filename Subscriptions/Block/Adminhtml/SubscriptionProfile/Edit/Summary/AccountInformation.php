<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Summary;

use Magento\Framework\Stdlib\DateTime;
use Magento\Backend\Block\Template;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile as SubscriptionProfileResource;

/**
 * Block for view subscription profile information
 */
class AccountInformation extends Template
{
    /**
     * label for not found date
     */
    const DATE_NOT_FOUND = 'N/A';

    /**
     * label for subscription with term=1
     */
    const UNTIL_CANCELED = 'Until canceled';

    /**
     * Subscription profile
     *
     * @var SubscriptionProfileInterface
     */
    private $subscriptionProfile;

    /**
     * Customer group
     *
     * @var GroupRepositoryInterface
     */
    private $groupRepository;

    /**
     * Profile status
     *
     * @var ProfileStatus
     */
    private $profileStatus;

    /**
     * Subscription profile resource
     *
     * @var SubscriptionProfileResource
     */
    private $subscriptionProfileResource;

    /**
     * Registry
     *
     * @var Registry
     */
    private $registry;

    /**
     * Timezone interface
     *
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * AccountInformation constructor.
     * @param TimezoneInterface $timezone
     * @param SubscriptionProfileResource $subscriptionProfileResource
     * @param ProfileStatus $profileStatus
     * @param GroupRepositoryInterface $groupRepository
     * @param Registry $registry
     * @param Template\Context $context
     * @param array $data
     * @internal param SubscriptionProfileResource $subscriptionProfile
     */
    public function __construct(
        TimezoneInterface $timezone,
        SubscriptionProfileResource $subscriptionProfileResource,
        ProfileStatus $profileStatus,
        GroupRepositoryInterface $groupRepository,
        Registry $registry,
        Template\Context $context,
        array $data = []

    ) {
        $this->setTemplate('TNW_Subscriptions::subscription_profile/summary/account_information.phtml');
        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->groupRepository = $groupRepository;
        $this->profileStatus = $profileStatus;
        $this->subscriptionProfileResource = $subscriptionProfileResource;
        $this->timezone = $timezone;
    }

    /**
     * Return subscription status
     *
     * @return null|string
     */
    public function getStatus()
    {
        $status = $this->getSubscriptionProfile()->getStatus();
        return $this->profileStatus->getLabelByValue($status);
    }

    /**
     * Return created at date by subscription
     *
     * @return string
     */
    public function getCreatedOn()
    {
        return $this->normalizeDateFormat($this->getSubscriptionProfile()->getCreatedAt());
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
            ->getLastOrderData($this->getSubscriptionProfile(), true);
        if (!empty($lastOrderData)) {
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
        if ($this->getSubscriptionProfile()->getTrialStartDate()) {
            $date = $this->getSubscriptionProfile()->getStartDate();
        }
        return $this->normalizeDateFormat($date);
    }

    /**
     * If term equal 1 return 'UNTIL_CANCELED',
     * if term equal 0 return last not submit order date.
     * If quites were not generated return 'N/A.'
     *
     * @return string
     */
    public function getSubscriptionEndsOn()
    {
        $class = 'ends-on';
        $result = self::DATE_NOT_FOUND;
        $term = $this->getSubscriptionProfile()->getTerm();
        $lastOrderData = $this->subscriptionProfileResource
            ->getLastOrderData($this->getSubscriptionProfile(), false);
        switch ($term) {
            case 0:
                $date = '';
                if (!empty($lastOrderData)) {
                    $date = $lastOrderData['scheduled_at'];
                }
                $result = $this->normalizeDateFormat($date);
                break;
            case 1:
                if (!empty($lastOrderData)) {
                    $result = __(self::UNTIL_CANCELED);
                    $class = 'until-canceled';
                }
                break;
            default:
                break;
        }

        return '<span class="' . $class . '">' . $result . '</span>';
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
        return $this->getSubscriptionProfile()->getProfileCurrencyCode();
    }


    /**
     * Return subscription website name
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->getSubscriptionProfile()->getWebsite()->getName();
    }

    /**
     * Return customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws NoSuchEntityException
     */
    private function getCustomer()
    {
        return $this->getSubscriptionProfile()->getCustomer();
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
            $result = $this->timezone->date($dateTime->strToTime($date))->format('F dS, Y');
        }
        return $result;
    }


    /**
     * Return subscription profile from registry
     *
     * @return mixed|SubscriptionProfileInterface
     */
    public function getSubscriptionProfile()
    {
        if ($this->subscriptionProfile === null) {
            $this->subscriptionProfile = $this->registry->registry('tnw_subscription_profile');
        }

        return $this->subscriptionProfile;
    }

}