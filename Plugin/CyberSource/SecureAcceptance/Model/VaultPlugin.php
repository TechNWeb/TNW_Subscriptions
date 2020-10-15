<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\CyberSource\SecureAcceptance\Model;

use Magento\Backend\Model\Session\Quote;
use Magento\Vault\Model\Method\Vault;

/**
 * Class VaultPlugin - sed to add additional checks for subscriptions logic
 */
class VaultPlugin
{
    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var mixed
     */
    private $tokenManagement;

    /**
     * @var bool
     */
    private $reBill = false;

    /**
     * VaultPlugin constructor.
     * @param Quote $quote
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        Quote $quote,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->quote = $quote;
        if ($moduleManager->isEnabled("CyberSource_SecureAcceptance")) {
            $this->tokenManagement = $objectManager
                ->get(\CyberSource\SecureAcceptance\Model\PaymentTokenManagement::class);
        }
    }

    /**
     * @param Vault $subject
     * @param $result
     * @return boolean
     */
    public function afterIsAvailable(
        Vault $subject,
        $result
    ) {
        if (!$result) {
            return $result;
        }
        if ($subject->getCode() != \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CC_VAULT_CODE) {
            return $result;
        }

        if (!$this->reBill) {
            if (!$customerId = $this->quote->getCustomerId()) {
                return false; // no vault for a blank customer
            }

            $tokens = $this->tokenManagement->getAvailableTokens(
                $customerId,
                \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE
            );
            if (empty($tokens)) {
                return false;
            }
        }

        return $result;
    }

    /**
     * @param Vault $subject
     * @param $result
     * @return string
     */
    public function afterGetFormBlockType(Vault $subject, $result)
    {
        if (!class_exists('\CyberSource\SecureAcceptance\Model\Ui\ConfigProvider')
            || $subject->getCode() != \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CC_VAULT_CODE
        ) {
            return $result;
        }

        return \CyberSource\SecureAcceptance\Block\Vault\Form::class;
    }

    /**
     * @return $this
     */
    public function setIsReBill()
    {
        $this->reBill = true;
        return $this;
    }
}
