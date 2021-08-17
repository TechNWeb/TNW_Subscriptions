<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Framework\Api\Filter;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use TNW\Subscriptions\Model\EmailNotifier;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;

/**
 * Data provider for cancel button form in popup.
 */
class ChangeStatusPopup extends AbstractDataProvider
{
    /**
     * Data persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Profile manager
     *
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Manager
     */
    private $profileManager;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param DataPersistorInterface $dataPersistor
     * @param ScopeConfigInterface $scopeConfig
     * @param Manager $profileManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        DataPersistorInterface $dataPersistor,
        ScopeConfigInterface $scopeConfig,
        Manager $profileManager,
        array $meta = [],
        array $data = []
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->scopeConfig = $scopeConfig;
        $this->profileManager = $profileManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $profileId = $this->dataPersistor->get('subscription_id');
        $data['change_status_popup']['entity_id'] = $profileId;
        $data['change_status_popup']['cycles_count'] = 0;

        $websiteId = $this->profileManager->loadProfile($profileId)->getWebsiteId();
        $enableCommentAdd = $this->scopeConfig->getValue(
            EmailNotifier::XML_PATH_ENABLE_STATUS_CHANGE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $websiteId
        );
        $data['change_status_popup']['disableCheckbox'] = $enableCommentAdd ? false : true;

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
        return $this;
    }

    public function getMeta()
    {
        parent::getMeta();

    }
}
