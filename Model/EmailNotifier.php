<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

/**
 * Class EmailNotifier
 * @package TNW\Subscriptions\Model
 */
class EmailNotifier
{
    const XML_PATH_EMAIL_IDENTITY = 'tnw_subscriptions_profile_options/emails/email_identity';
    const XML_PATH_STATUS_CHANGE_TEMPLATE = 'tnw_subscriptions_profile_options/emails/profile_status_change';
    const XML_PATH_COMMENT_ADDED_TEMPLATE = 'tnw_subscriptions_profile_options/emails/comment_added';
    const XML_PATH_CARD_EXPIRE = 'tnw_subscriptions_profile_options/emails/card_expire';
    const XML_PATH_PAYMENT_FAILED = 'tnw_subscriptions_profile_options/emails/payment_failed';
    const XML_PATH_RENEWAL = 'tnw_subscriptions_profile_options/emails/renewal';

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
     * EmailNotifier constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->inlineTranslation = $inlineTranslation;
    }

    /**
     * @param $subscriptionProfile
     * @param $oldStatus
     * @param $newStatus
     */
    public function profileStatusChange($subscriptionProfile, $oldStatus, $newStatus)
    {
        if ($this->checkEmailTemplateSetting(self::XML_PATH_STATUS_CHANGE_TEMPLATE)) {
            $customer = $subscriptionProfile->getCustomer();
            $this->sendNotificationEmail(
                $this->scopeConfig->getValue(
                    self::XML_PATH_STATUS_CHANGE_TEMPLATE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                $customer->getStoreId(),
                [
                    'subscription' => $subscriptionProfile,
                    'oldStatus' => $oldStatus ,
                    'newStatus' => $newStatus,
                    'customer' => $customer
                ],
                [
                    'email' => $customer->getEmail(),
                    'name' => $customer->getFirstname() . ' ' . $customer->getFirstname()
                ]
            );
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
            $customer = $subscriptionProfile->getCustomer();
            $this->sendNotificationEmail(
                $this->scopeConfig->getValue(
                    self::XML_PATH_COMMENT_ADDED_TEMPLATE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                $customer->getStoreId(),
                [
                    'subscription' => $subscriptionProfile,
                    'comment' => $comment ,
                    'customer' => $customer
                ],
                [
                    'email' => $customer->getEmail(),
                    'name' => $customer->getFirstname() . ' ' . $customer->getFirstname()
                ]
            );
        }
    }

    /**
     * @param $subscriptionProfile
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function cardExpire($subscriptionProfile)
    {
        if ($this->checkEmailTemplateSetting(self::XML_PATH_CARD_EXPIRE)) {
            $customer = $subscriptionProfile->getCustomer();
            $this->sendNotificationEmail(
                $this->scopeConfig->getValue(
                    self::XML_PATH_CARD_EXPIRE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                $customer->getStoreId(),
                [
                    'subscription' => $subscriptionProfile,
                    'customer' => $customer
                ],
                [
                    'email' => $customer->getEmail(),
                    'name' => $customer->getFirstname() . ' ' . $customer->getFirstname()
                ]
            );
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
            $customer = $subscriptionProfile->getCustomer();
            $this->sendNotificationEmail(
                $this->scopeConfig->getValue(
                    self::XML_PATH_PAYMENT_FAILED,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                $customer->getStoreId(),
                [
                    'subscription' => $subscriptionProfile,
                    'customer' => $customer
                ],
                [
                    'email' => $customer->getEmail(),
                    'name' => $customer->getFirstname() . ' ' . $customer->getFirstname()
                ]
            );
        }
    }

    /**
     * @param $subscriptionProfile
     * @param $date
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function renewal($subscriptionProfile, $date)
    {
        if ($this->checkEmailTemplateSetting(self::XML_PATH_RENEWAL)) {
            $customer = $subscriptionProfile->getCustomer();
            $this->sendNotificationEmail(
                $this->scopeConfig->getValue(
                    self::XML_PATH_RENEWAL,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                $customer->getStoreId(),
                [
                    'subscription' => $subscriptionProfile,
                    'customer' => $customer,
                    'date' => $date
                ],
                [
                    'email' => $customer->getEmail(),
                    'name' => $customer->getFirstname() . ' ' . $customer->getFirstname()
                ]
            );
        }
    }

    /**
     * @param $templateIdentifier
     * @param $storeId
     * @param array $vars
     * @param array $to
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendNotificationEmail($templateIdentifier, $storeId, $vars = [], $to = [])
    {
        $this->inlineTranslation->suspend();
        $this->transportBuilder->setTemplateIdentifier($templateIdentifier)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId,
                ]
            )->setTemplateVars(
                $vars
            )->setFrom(
                $this->scopeConfig->getValue(
                    self::XML_PATH_EMAIL_IDENTITY,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->addTo(
                $to['email'],
                $to['name']
            );
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
        return $this;
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
}
