<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Summary;

use Magento\Backend\Block\Template;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile as SubscriptionProfileResource;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use TNW\Subscriptions\Model\BillingFrequencyRepository;
use Psr\Log\LoggerInterface;

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
     * @var GroupManagementInterface
     */
    private $groupManagement;

    /**
     * @var Manager
     */
    private $profileManager;

    /**
     * @var BillingFrequencyRepository
     */
    private $billingFrequencyRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AccountInformation constructor.
     * @param SubscriptionProfileResource $subscriptionProfileResource
     * @param ProfileStatus $profileStatus
     * @param GroupRepositoryInterface $groupRepository
     * @param Registry $registry
     * @param Template\Context $context
     * @param Manager $profileManager
     * @param GroupManagementInterface $groupManagement
     * @param array $data
     */
    public function __construct(
        SubscriptionProfileResource $subscriptionProfileResource,
        ProfileStatus $profileStatus,
        GroupRepositoryInterface $groupRepository,
        Registry $registry,
        Template\Context $context,
        Manager $profileManager,
        GroupManagementInterface $groupManagement,
        BillingFrequencyRepository $billingFrequencyRepository,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->setTemplate('TNW_Subscriptions::subscription_profile/summary/account_information.phtml');
        parent::__construct($context, $data);
        $this->profileManager = $profileManager;
        $this->groupManagement = $groupManagement;
        $this->registry = $registry;
        $this->groupRepository = $groupRepository;
        $this->profileStatus = $profileStatus;
        $this->subscriptionProfileResource = $subscriptionProfileResource;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        $this->logger = $logger;
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
     * if term equal 0 return calculated end subscription date.
     *
     * @return string
     */
    public function getSubscriptionEndsOn()
    {
        $class = 'ends-on';
        $result = self::DATE_NOT_FOUND;
        $profile = $this->getSubscriptionProfile();
        try {
            $billingFrequency = $this->billingFrequencyRepository->getById(
                $profile->getBillingFrequencyId()
            );
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
            $billingFrequency = null;
        }
        $term = $profile->getTerm();
        $lastOrderData = $this->subscriptionProfileResource
            ->getLastOrderData($this->getSubscriptionProfile(), false);
        switch ($term) {
            case 0:
                $date = '';
                if (!empty($lastOrderData) && $billingFrequency !== null) {
                    $date = $profile->getFinalDate(
                        $billingFrequency,
                        $profile->getStaticTotalBillingCycles(),
                        $profile->getStartDate(),
                        $term
                    );
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
        if ($customer = $this->getCustomer()) {
            $customerName = $customer->getFirstname();
        } else {
            $customerName = $this->profileManager
                ->getLastProfileOrder($this->getSubscriptionProfile())
                ->getCustomerName();
        }
        return $customerName;
    }

    /**
     * Return customer url
     *
     * @return string
     */
    public function getCustomerUrl()
    {
        if ($this->getCustomer()) {
            $url = $this->_urlBuilder->getUrl('customer/index/edit', ['id' => $this->getCustomer()->getId()]);
        } else {
            $url = '#';
        }

        return $url;
    }

    /**
     * Return customer email
     *
     * @return string
     */
    public function getEmail()
    {
        if ($customer = $this->getCustomer()) {
            $customerEmail = $customer->getEmail();
        } else {
            $customerEmail = $this->profileManager
                ->getLastProfileOrder($this->getSubscriptionProfile())
                ->getCustomerEmail();
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

        if ($this->getCustomer() && is_numeric($this->getCustomer()->getGroupId())) {
            try {
                /** @var \Magento\Customer\Model\Data\Group $group */
                $group = $this->groupRepository->getById($this->getCustomer()->getGroupId());
            } catch (\Exception $e) {
                $group = null;
            }
        } else {
            try {
                $group = $this->groupManagement->getNotLoggedInGroup();
            } catch (\Exception $e) {
                $group = null;
            }
        }
        if ($group) {
            $groupCode = $group->getCode();
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
            $result = $this->_localeDate->formatDate($date, \IntlDateFormatter::LONG);
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
