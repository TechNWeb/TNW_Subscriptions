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
    private  $currentVaultMethod;

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
        CartRepositoryInterface $cartRepository
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
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        foreach ($this->vaultConfigProvider->getConfig()['vault'] as $vaultCode => $isEnabled) {
            if ($isEnabled) {
                $this->vaultMethods[] = $vaultCode;
            }
        }
        if (empty($this->vaultMethods)) {
            return $meta;
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
            $ccTypeLabel = $this->getCcTypeLabel($ccToken->getConfig()['details']['type']);
            $ccTitle = $ccTypeLabel . ' ending ' . $ccToken->getConfig()['details']['maskedCC'] . ' (expires: ' .
                $ccToken->getConfig()['details']['expirationDate'] . ')';
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
        //TODO: get actual vault title
        return 'Stored Cards '. $this->getPaymentCode();
    }
}
