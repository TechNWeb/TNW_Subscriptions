<?php
/**
 *  Copyright © 2022 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Plugin\AdminGws\Model;

use Magento\AdminGws\Model\Role;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class Models - plugin to grant acces for customer model loading if customer sharing is global
 */
class Models
{
    /**
     * @var Role
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
     * @param Role $role
     * @param ScopeConfigInterface $scopeConfig
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Role $role,
        ScopeConfigInterface $scopeConfig,
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->scopeConfig = $scopeConfig;
        $this->role = $role;
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
