<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Framework\Module\Manager;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Field;
use Magento\Vault\Model\Ui\Adminhtml\TokensConfigProvider;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Class Vault
 * @package TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier
 */
class Vault extends Base
{
    /**
     * @var string
     */
    private $tokensConfig = [];

    /**
     * @var TokensConfigProvider
     */
    private $tokensConfigProvider;

    /**
     * @var string
     */
    private  $currentVaultMethod = 'vault';

    /**
     * @var array
     */
    private $vaultMethods = [];

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @var CcConfig
     */
    private $ccConfig;

    /**
     * @var VaultConfigProvider
     */
    private $vaultConfigProvider;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $sessionManager;

    /**
     * Vault constructor.
     * @param TokensConfigProvider $tokensConfigProvider
     * @param Manager $moduleManager
     * @param CcConfig $ccConfig
     * @param VaultConfigProvider $vaultConfigProvider
     * @param Config $config
     * @param QuoteSessionInterface $session
     * @param SubscriptionProfileRepository $profileRepository
     * @param OrderRelationManager $relationManager
     * @param CartRepositoryInterface $cartRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     */
    public function __construct(
        TokensConfigProvider $tokensConfigProvider,
        Manager $moduleManager,
        CcConfig $ccConfig,
        VaultConfigProvider $vaultConfigProvider,
        Config $config,
        QuoteSessionInterface $session,
        SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        CartRepositoryInterface $cartRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager
    ) {
        parent::__construct($config, $session, $profileRepository, $relationManager, $cartRepository);
        /**
         * TODO: tokensConfigProvider has different classes for admin & frontend:
         * \Magento\Vault\Model\Ui\Adminhtml\TokensConfigProvider
         * \Magento\Vault\Model\Ui\TokensConfigProvider
         */
        $this->tokensConfigProvider = $tokensConfigProvider;
        $this->moduleManager = $moduleManager;
        $this->config = $config;
        $this->session = $session;
        $this->ccConfig = $ccConfig;
        $this->vaultConfigProvider = $vaultConfigProvider;
        $this->scopeConfig = $scopeConfig;
        $this->sessionManager = $sessionManager;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        foreach ($this->vaultConfigProvider->getConfig()['vault'] as $vaultCode => $enabledConfig) {
            if (
            $this->config->isPaymentMethodAvailableForSubscription(
                str_replace(['_cc_vault', '_vault'], '', $vaultCode),
                $this->session->getStoreId()
            )
            ) {
                $this->vaultMethods[] = $vaultCode;
            }
        }
        if (empty($this->vaultMethods)) {
            return $meta;
        }
        $customerId = $this->session->getCustomerId();
        if (
            (!$this->sessionManager->getCustomerId() && $customerId)
            || $this->sessionManager->getCustomerId() != $customerId
        ) {
            $this->sessionManager->setCustomerId($customerId);
        }
        foreach ($this->vaultMethods as $method) {
            $this->tokensConfig[$method] = $this->tokensConfigProvider->getTokensComponents($method);
            if (empty($this->tokensConfig[$method])) continue;
            $this->currentVaultMethod = $method;

            $meta = array_replace_recursive(
                $meta,
                $this->getPaymentFields()
            );
        }
        return $meta;
    }

    /**
     * @return array
     */
    protected function getAdditionalFields()
    {
        $cards = [];
        $checked = true;
        foreach ($this->tokensConfig[$this->currentVaultMethod] as $ccToken) {
            $ccType = isset($ccToken->getConfig()['details']['type'])
                ? $ccToken->getConfig()['details']['type']
                : $ccToken->getConfig()['details']['cc_type'];
            $ccTypeLabel = $this->getCcTypeLabel($ccType);
            $maskedCC = isset($ccToken->getConfig()['details']['maskedCC'])
                ? $ccToken->getConfig()['details']['maskedCC']
                : $ccToken->getConfig()['details']['cc_last_4'];
            $expDate = isset($ccToken->getConfig()['details']['expirationDate'])
                ? $ccToken->getConfig()['details']['expirationDate']
                : $ccToken->getConfig()['details']['cc_exp_month']
                    . '/'
                    . $ccToken->getConfig()['details']['cc_exp_year'];
            $ccTitle = $ccTypeLabel
                . ' ending '
                . $maskedCC
                . ' (expires: '
                . $expDate
                . ')';
            $pubHash = $ccToken->getConfig()['publicHash'];
            $cards[$pubHash] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => Checkbox::NAME,
                            'componentType' => Field::NAME,
                            'prefer' => 'radio',
                            'description' => $ccTitle,
                            'value' => $pubHash,
                            'checked' => $checked,
                            'dataScope' => 'publicHash',
                            'elementTmpl' => 'TNW_Subscriptions/form/element/radio',
                            'validation' => [
                                'required-entry' => true
                            ],
                            'imports' => [
                                'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                            ],
                        ],
                    ],
                ]
            ];
            $checked = false;
        }
        return $cards;
    }

    /**
     * @return array
     */
    protected function getAdditionalConfig()
    {
        return [
            'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/base',
            'options' => [
                'formName' => $this->getPaymentFormName(),
            ],
        ];
    }

    /**
     * @param $ccCode
     * @return mixed
     */
    protected function getCcTypeLabel($ccCode)
    {
        return $this->ccConfig->getCcAvailableTypes()[$ccCode];
    }

    /**
     * @return string
     */
    protected function getPaymentCode()
    {
        return $this->currentVaultMethod;
    }

    /**
     * @return string
     */
    protected function getPaymentTitle()
    {
        return $this->scopeConfig->getValue('payment/' .  $this->getPaymentCode() . '/title');
    }
}
