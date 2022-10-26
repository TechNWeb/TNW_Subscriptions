<?php
/**
 *  Copyright © 2022 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Plugin\AdminGws\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Models - plugin to grant acces for customer model loading if customer sharing is global
 */
class Models
{
    /**
     * @var mixed
     */
    protected $role;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * Models constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param DataPersistorInterface $dataPersistor
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DataPersistorInterface $dataPersistor,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        if ($moduleManager->isEnabled("Magento_AdminGws")) {
            $this->role = $objectManager
                ->get(\Magento\AdminGws\Model\Role::class);
        }
        $this->dataPersistor = $dataPersistor;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $subject
     * @param callable $proceed
     * @param $model
     * @throws LocalizedException
     */
    public function aroundCustomerLoadAfter(
        $subject,
        callable $proceed,
        $model
    ) {
        try {
            $proceed($model);
        } catch (LocalizedException $e) {
            if (!($this->scopeConfig->getValue('customer/account_share/scope') == Share::SHARE_GLOBAL
                && $this->role->getStoreIds()
                && $this->dataPersistor->get('manual_queue_processing')
            )) {
                throw $e;
            }
        }
    }
}
