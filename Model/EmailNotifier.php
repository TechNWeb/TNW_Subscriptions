<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Class EmailNotifier - used for email notifications
 */
class EmailNotifier
{
    const XML_PATH_MODULE_ENABLE = 'tnw_subscriptions_general/general/active';

    const XML_PATH_EMAIL_IDENTITY = 'tnw_subscriptions_profile_options/emails/email_identity';

    const XML_PATH_RENEWAL_NOTIFICATION_PERIOD = 'tnw_subscriptions_profile_notification/notifications/renewals';
    const XML_PATH_RENEWAL_SECOND_NOTIFICATION_PERIOD =
        'tnw_subscriptions_profile_notification/notifications/renewals_two';
    const XML_PATH_EXPIRED_CARD_NOTIFICATION_PERIOD =
        'tnw_subscriptions_profile_notification/notifications/expired_card';

    const XML_PATH_STATUS_CHANGE_TEMPLATE =
        'tnw_subscriptions_profile_notification/status_change_setting/profile_status_change';
    const XML_PATH_COMMENT_ADDED_TEMPLATE = 'tnw_subscriptions_profile_notification/send_comment_setting/comment_added';
    const XML_PATH_CARD_EXPIRE = 'tnw_subscriptions_profile_notification/card_expire_setting/card_expire';
    const XML_PATH_PAYMENT_FAILED = 'tnw_subscriptions_profile_notification/payment_failed_setting/payment_failed';
    const XML_PATH_PAYMENT_VERIFICATION_FAILED =
        'tnw_subscriptions_profile_notification/payment_failed_setting/payment_verification_failed';
    const XML_PATH_OUT_OF_STOCK = 'tnw_subscriptions_profile_notification/out_of_stock_setting/out_of_stock';
    const XML_PATH_RENEWAL = 'tnw_subscriptions_profile_notification/renewal_setting/renewal';

    const XML_PATH_ENABLE_COMMENT_ADDED =
        'tnw_subscriptions_profile_notification/send_comment_setting/comment_added_enable';
    const XML_PATH_ENABLE_STATUS_CHANGE =
        'tnw_subscriptions_profile_notification/status_change_setting/status_change_enable';
    const XML_PATH_ENABLE_CARD_EXPIRE =
        'tnw_subscriptions_profile_notification/card_expire_setting/card_expire_enable';
    const XML_PATH_ENABLE_PAYMENT_FAILED =
        'tnw_subscriptions_profile_notification/payment_failed_setting/payment_failed_enable';
    const XML_PATH_ENABLE_PAYMENT_RENEWAL = 'tnw_subscriptions_profile_notification/renewal_setting/renewal_enable';
    const XML_PATH_ENABLE_OUT_OF_STOCK =
        'tnw_subscriptions_profile_notification/out_of_stock_setting/out_of_stock_enable';

    const XML_PATH_COMMENT_ADDED_COPY_TO =
        'tnw_subscriptions_profile_notification/send_comment_setting/comment_added_copy_to';
    const XML_PATH_COMMENT_ADDED_COPY_METHOD =
        'tnw_subscriptions_profile_notification/send_comment_setting/comment_added_copy_method';

    const XML_PATH_STATUS_CHANGE_COPY_TO =
        'tnw_subscriptions_profile_notification/status_change_setting/status_change_copy_to';
    const XML_PATH_STATUS_CHANGE_COPY_METHOD =
        'tnw_subscriptions_profile_notification/status_change_setting/status_change_copy_method';

    const XML_PATH_CARD_EXPIRE_COPY_TO =
        'tnw_subscriptions_profile_notification/card_expire_setting/card_expire_copy_to';
    const XML_PATH_CARD_EXPIRE_COPY_METHOD =
         'tnw_subscriptions_profile_notification/card_expire_setting/card_expire_copy_method';

    const XML_PATH_PAYMENT_FAILED_COPY_TO =
        'tnw_subscriptions_profile_notification/payment_failed_setting/payment_failed_copy_to';
    const XML_PATH_PAYMENT_FAILED_COPY_METHOD =
        'tnw_subscriptions_profile_notification/payment_failed_setting/payment_failed_copy_method';

    const XML_PATH_PAYMENT_RENEWAL_COPY_TO = 'tnw_subscriptions_profile_notification/renewal_setting/renewal_copy_to';
    const XML_PATH_PAYMENT_RENEWAL_COPY_METHOD =
        'tnw_subscriptions_profile_notification/renewal_setting/renewal_copy_method';

    const XML_PATH_OUT_OF_STOCK_COPY_TO =
        'tnw_subscriptions_profile_notification/out_of_stock_setting/out_of_stock_copy_to';
    const XML_PATH_OUT_OF_STOCK_COPY_METHOD =
        'tnw_subscriptions_profile_notification/out_of_stock_setting/out_of_stock_copy_method';

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var Source\ProfileStatusFactory
     */
    protected $profileStatusFactory;

    /**
     * @var \TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface
     */
    protected $subscriptionProfileRepository;
    /**
     * @var SubscriptionProfileOrder\Manager
     */
    private $profileOrderManager;

    /**
     * @var SubscriptionProfile\Manager
     */
    private $profileManager;

    private $paymentNotificationErrorSent = [];

    /**
     * EmailNotifier constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param Source\ProfileStatusFactory $profileStatusFactory
     * @param \TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     * @param SubscriptionProfileOrder\Manager $profileOrderManager
     * @param SubscriptionProfile\Manager $profileManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        Source\ProfileStatusFactory $profileStatusFactory,
        \TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        SubscriptionProfileOrder\Manager $profileOrderManager,
        SubscriptionProfile\Manager $profileManager
    ) {
        $this->profileManager = $profileManager;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->profileStatusFactory = $profileStatusFactory;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->inlineTranslation = $inlineTranslation;
        $this->profileOrderManager = $profileOrderManager;
    }

    /**
     * @param $subscriptionProfile
     * @param $oldStatus
     * @param $newStatus
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function profileStatusChange($subscriptionProfile, $oldStatus, $newStatus)
    {
        if ($this->checkEmailTemplateSetting(self::XML_PATH_STATUS_CHANGE_TEMPLATE)) {
            list($storeId, $customerEmail, $customerName) = $this->getCustomerVars($subscriptionProfile);
            $statusModel = $this->profileStatusFactory->create();
            $date = $this->getNextProfileRelation($subscriptionProfile)
                ? date('F jS, Y', strtotime($this->getNextProfileRelation($subscriptionProfile)->getScheduledAt()))
                : null;
            $enableEmailNotification = $this->scopeConfig->getValue(
                self::XML_PATH_ENABLE_STATUS_CHANGE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $moduleEnable = $this->scopeConfig->getValue(
                self::XML_PATH_MODULE_ENABLE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($enableEmailNotification == 1 && $moduleEnable == 1) {
                $copyTo = $this->getEmailCopyTo(self::XML_PATH_STATUS_CHANGE_COPY_TO, $storeId);
                $copyMethod = $this->getCopyMethod(self::XML_PATH_STATUS_CHANGE_COPY_METHOD, $storeId);
                $this->sendNotificationEmail(
                    $this->scopeConfig->getValue(
                        self::XML_PATH_STATUS_CHANGE_TEMPLATE,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    ),
                    $storeId,
                    [
                        'subscription' => $subscriptionProfile,
                        'subscription_data' => [
                            'id' => $subscriptionProfile->getId(),
                            'label' => $subscriptionProfile->getLabel()
                        ],
                        'oldStatus' => (string) $statusModel->getLabelByValue($oldStatus) ?? '',
                        'newStatus' => (string) $statusModel->getLabelByValue($newStatus) ?? '',
                        'date' => $date,
                        'customerName' => $customerName
                    ],
                    [
                        'email' => $customerEmail,
                        'name' => $customerName
                    ],
                    $copyTo,
                    $copyMethod
                );
            }
        }
    }

    /**
     * @param $subscriptionProfile
     * @param $comment
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function addComment($subscriptionProfile, $comment)
    {
        if ($this->checkEmailTemplateSetting(self::XML_PATH_COMMENT_ADDED_TEMPLATE)) {
            if (is_numeric($subscriptionProfile)) {
                try {
                    $subscriptionProfile = $this->subscriptionProfileRepository->getById($subscriptionProfile);
                } catch (\Exception $e) {
                    $subscriptionProfile = null;
                }
            }
            if ($subscriptionProfile) {
                list($storeId, $customerEmail, $customerName) = $this->getCustomerVars($subscriptionProfile);
                $enableEmailNotification = $this->scopeConfig->getValue(
                    self::XML_PATH_ENABLE_COMMENT_ADDED,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                );
                $moduleEnable = $this->scopeConfig->getValue(
                    self::XML_PATH_MODULE_ENABLE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                );
                if ($enableEmailNotification == 1 && $moduleEnable == 1) {
                    $copyTo = $this->getEmailCopyTo(self::XML_PATH_COMMENT_ADDED_COPY_TO, $storeId);
                    $copyMethod = $this->getCopyMethod(self::XML_PATH_COMMENT_ADDED_COPY_METHOD, $storeId);
                    $this->sendNotificationEmail(
                        $this->scopeConfig->getValue(
                            self::XML_PATH_COMMENT_ADDED_TEMPLATE,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        $storeId,
                        [
                            'subscription' => $subscriptionProfile,
                            'subscription_data' => [
                                'id' => $subscriptionProfile->getId(),
                                'label' => $subscriptionProfile->getLabel()
                            ],
                            'comment' => $comment,
                            'customerName' => $customerName
                        ],
                        [
                            'email' => $customerEmail,
                            'name' => $customerName
                        ],
                        $copyTo,
                        $copyMethod
                    );
                }
            }
        }
    }

    /**
     * @param $subscriptionProfile
     * @param $date
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function cardExpire($subscriptionProfile, $date)
    {
        if ($this->checkEmailTemplateSetting(self::XML_PATH_CARD_EXPIRE)) {
            list($storeId, $customerEmail, $customerName) = $this->getCustomerVars($subscriptionProfile);
            $enableEmailNotification = $this->scopeConfig->getValue(
                self::XML_PATH_ENABLE_CARD_EXPIRE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $moduleEnable = $this->scopeConfig->getValue(
                self::XML_PATH_MODULE_ENABLE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($enableEmailNotification == 1 && $moduleEnable == 1) {
                $copyTo = $this->getEmailCopyTo(self::XML_PATH_CARD_EXPIRE_COPY_TO, $storeId);
                $copyMethod = $this->getCopyMethod(self::XML_PATH_CARD_EXPIRE_COPY_METHOD, $storeId);
                $this->sendNotificationEmail(
                    $this->scopeConfig->getValue(
                        self::XML_PATH_CARD_EXPIRE,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    ),
                    $storeId,
                    [
                        'subscription' => $subscriptionProfile,
                        'subscription_data' => [
                            'id' => $subscriptionProfile->getId(),
                            'label' => $subscriptionProfile->getLabel()
                        ],
                        'customerName' => $customerName,
                        'date' => date('F jS, Y', strtotime($date))
                    ],
                    [
                        'email' => $customerEmail,
                        'name' => $customerName
                    ],
                    $copyTo,
                    $copyMethod
                );
            }
        }
    }

    /**
     * @param $subscriptionProfile
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function paymentFailed($subscriptionProfile)
    {
        if ($this->checkEmailTemplateSetting(self::XML_PATH_PAYMENT_FAILED)) {
            list($storeId, $customerEmail, $customerName) = $this->getCustomerVars($subscriptionProfile);
            $enableEmailNotification = $this->scopeConfig->getValue(
                self::XML_PATH_ENABLE_PAYMENT_FAILED,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $moduleEnable = $this->scopeConfig->getValue(
                self::XML_PATH_MODULE_ENABLE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($enableEmailNotification == 1
                && $moduleEnable == 1
                && !isset($this->paymentNotificationErrorSent[$subscriptionProfile->getId()])
            ) {
                $copyTo = $this->getEmailCopyTo(self::XML_PATH_PAYMENT_FAILED_COPY_TO, $storeId);
                $copyMethod = $this->getCopyMethod(self::XML_PATH_PAYMENT_FAILED_COPY_METHOD, $storeId);
                $this->sendNotificationEmail(
                    $this->scopeConfig->getValue(
                        self::XML_PATH_PAYMENT_FAILED,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    ),
                    $storeId,
                    [
                        'subscription' => $subscriptionProfile,
                        'subscription_data' => [
                            'id' => $subscriptionProfile->getId(),
                            'label' => $subscriptionProfile->getLabel()
                        ],
                        'customerName' => $customerName,
                        'attempt_interval' => $this->scopeConfig->getValue(
                            'tnw_subscriptions_profile_options/past_due_profile_options/attempt_interval',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        )
                    ],
                    [
                        'email' => $customerEmail,
                        'name' => $customerName
                    ],
                    $copyTo,
                    $copyMethod
                );
                $this->paymentNotificationErrorSent[$subscriptionProfile->getId()] = true;
            }
        }
    }

    /**
     * @param $subscriptionProfile
     * @param $reBill
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function paymentVerificationFailed($subscriptionProfile, $reBill)
    {
        if ($this->checkEmailTemplateSetting(self::XML_PATH_PAYMENT_FAILED)) {
            list($storeId, $customerEmail, $customerName) = $this->getCustomerVars($subscriptionProfile);
            $enableEmailNotification = $this->scopeConfig->getValue(
                self::XML_PATH_ENABLE_PAYMENT_FAILED,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $moduleEnable = $this->scopeConfig->getValue(
                self::XML_PATH_MODULE_ENABLE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($enableEmailNotification == 1
                && $moduleEnable == 1
                && !isset($this->paymentNotificationErrorSent[$subscriptionProfile->getId()])
            ) {
                $copyTo = $this->getEmailCopyTo(self::XML_PATH_PAYMENT_FAILED_COPY_TO, $storeId);
                $copyMethod = $this->getCopyMethod(self::XML_PATH_PAYMENT_FAILED_COPY_METHOD, $storeId);
                $this->sendNotificationEmail(
                    $this->scopeConfig->getValue(
                        self::XML_PATH_PAYMENT_VERIFICATION_FAILED,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    ),
                    $storeId,
                    [
                        'subscription' => $subscriptionProfile,
                        'subscription_data' => [
                            'id' => $subscriptionProfile->getId(),
                            'label' => $subscriptionProfile->getLabel()
                        ],
                        'customerName' => $customerName,
                        'attempt_interval' => $this->scopeConfig->getValue(
                            'tnw_subscriptions_profile_options/past_due_profile_options/attempt_interval',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        'verification_token' => $reBill->getToken()
                    ],
                    [
                        'email' => $customerEmail,
                        'name' => $customerName
                    ],
                    $copyTo,
                    $copyMethod
                );
                $this->paymentNotificationErrorSent[$subscriptionProfile->getId()] = true;
            }
        }
    }

    /**
     * @param $subscriptionProfile
     * @param $products
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function outOfStockProducts($subscriptionProfile, $products)
    {
        if ($this->checkEmailTemplateSetting(self::XML_PATH_OUT_OF_STOCK)) {
            list($storeId, $customerEmail, $customerName) = $this->getCustomerVars($subscriptionProfile);
            $enableEmailNotification = $this->scopeConfig->getValue(
                self::XML_PATH_ENABLE_OUT_OF_STOCK,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $moduleEnable = $this->scopeConfig->getValue(
                self::XML_PATH_MODULE_ENABLE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($enableEmailNotification == 1 && $moduleEnable == 1) {
                $copyTo = $this->getEmailCopyTo(self::XML_PATH_OUT_OF_STOCK_COPY_TO, $storeId);
                $copyMethod = $this->getCopyMethod(self::XML_PATH_OUT_OF_STOCK_COPY_METHOD, $storeId);
                $this->sendNotificationEmail(
                    $this->scopeConfig->getValue(
                        self::XML_PATH_OUT_OF_STOCK,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    ),
                    $storeId,
                    [
                        'subscription' => $subscriptionProfile,
                        'subscription_data' => [
                            'id' => $subscriptionProfile->getId(),
                            'label' => $subscriptionProfile->getLabel()
                        ],
                        'customerName' => $customerName,
                        'products' => implode(', ', $products)
                    ],
                    [
                        'email' => $customerEmail,
                        'name' => $customerName
                    ],
                    $copyTo,
                    $copyMethod
                );
            }
        }
    }

    /**
     * @param $profileIds
     * @param $date
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function renewal($profileIds, $date)
    {
        if ($this->checkEmailTemplateSetting(self::XML_PATH_RENEWAL)) {
            $subscriptionProfiles = [];
            $labels = [];
            $subscriptionProfilesLabels = '';
            if (is_array($profileIds)) {
                foreach ($profileIds as $profileId) {
                    try {
                        $profile = $this->subscriptionProfileRepository->getById($profileId);
                        $subscriptionProfiles[] = $profile;
                        $labels[] = $profile->getLabel();
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                $subscriptionProfilesLabels = implode(', ', $labels);
            }

            if (!empty($subscriptionProfiles)) {
                $subscriptionProfile = reset($subscriptionProfiles);
                list($storeId, $customerEmail, $customerName) = $this->getCustomerVars($subscriptionProfile);
                $enableEmailNotification = $this->scopeConfig->getValue(
                    self::XML_PATH_ENABLE_PAYMENT_RENEWAL,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                );
                $moduleEnable = $this->scopeConfig->getValue(
                    self::XML_PATH_MODULE_ENABLE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                );
                if ($subscriptionProfile->getStatus() == ProfileStatus::STATUS_PAST_DUE
                    || $subscriptionProfile->getStatus() == ProfileStatus::STATUS_ACTIVE
                    && !$subscriptionProfile->getCancelBeforeNextCycle()
                    && $enableEmailNotification == 1 && $moduleEnable == 1) {
                    $copyTo = $this->getEmailCopyTo(self::XML_PATH_PAYMENT_RENEWAL_COPY_TO, $storeId);
                    $copyMethod = $this->getCopyMethod(self::XML_PATH_PAYMENT_RENEWAL_COPY_METHOD, $storeId);
                    $this->sendNotificationEmail(
                        $this->scopeConfig->getValue(
                            self::XML_PATH_RENEWAL,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId
                        ),
                        $storeId,
                        [
                            'subscriptions' => $subscriptionProfiles,
                            'subscriptions_labels' => $subscriptionProfilesLabels,
                            'subscription_profile_ids' => $profileIds,
                            'customerName' => $customerName,
                            'date' => date('F jS, Y', strtotime($date))
                        ],
                        [
                            'email' => $customerEmail,
                            'name' => $customerName
                        ],
                        $copyTo,
                        $copyMethod
                    );
                }
            }
        }
    }

    /**
     * @param $templateIdentifier
     * @param $storeId
     * @param array $vars
     * @param array $to
     * @param string $copyTo
     * @param string $copyMethod
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendNotificationEmail(
        $templateIdentifier,
        $storeId,
        $vars = [],
        $to = [],
        $copyTo = [],
        $copyMethod = ''
    ) {
        $this->inlineTranslation->suspend();
        $this->transportBuilder->setTemplateIdentifier($templateIdentifier)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId,
                ]
            )->setTemplateVars(
                $vars
            )->setFromByScope(
                $this->scopeConfig->getValue(
                    self::XML_PATH_EMAIL_IDENTITY,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                $storeId
            )->addTo(
                $to['email'],
                $to['name']
            );

        if (!empty($copyTo) && $copyMethod == 'bcc') {
            foreach ($copyTo as $email) {
                $this->transportBuilder->addBcc($email);
            }
        }
        if (!empty($copyTo) && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $this->transportBuilder->addCc($email);
            }
        }

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
        return $this;
    }

    /**
     * @param $subscriptionProfile
     * @return array
     */
    public function getCustomerVars($subscriptionProfile)
    {
        $customer = $subscriptionProfile->getCustomer();
        $order = $this->profileManager->getLastProfileOrder($subscriptionProfile);
        $storeId = $order->getStoreId();
        if (!$storeId && $customer) {
            $storeId = $customer->getStoreId();
        }
        if (!$customer) {
            $customerEmail = $order->getCustomerEmail();
            $customerName = $order->getCustomerName();
        } else {
            $customerEmail = $customer->getEmail();
            $customerName = $customer->getFirstname() . ' ' . $customer->getLastName();
        }
        return [$storeId, $customerEmail, $customerName];
    }

    /**
     * @param $configPath
     * @return bool
     */
    private function checkEmailTemplateSetting($configPath)
    {
        if (!$this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) || !$this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_IDENTITY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )
        ) {
            return false;
        }
        return true;
    }

    /**
     * Returns next profile relation instance.
     *
     * @param SubscriptionProfile $profile
     * @return false|\TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface
     */
    private function getNextProfileRelation(SubscriptionProfile $profile)
    {
        return $this->profileOrderManager->getNextProfileRelation($profile, false);
    }

    /**
     * Return email copy_to list
     *
     * @param $path
     * @param $storeCode
     * @return array|bool
     */
    public function getEmailCopyTo($path, $storeCode)
    {
        $data = $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeCode);
        if (!empty($data)) {
            return array_map('trim', explode(',', $data));
        }
        return false;
    }

    /**
     * @param $path
     * @param $storeCode
     * @return mixed
     */
    public function getCopyMethod($path, $storeCode)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeCode);
    }
}
