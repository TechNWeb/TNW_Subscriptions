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
use Magento\Store\Model\StoreManagerInterface;

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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Manager
     */
    private $profileManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param DataPersistorInterface $dataPersistor
     * @param ScopeConfigInterface $scopeConfig
     * @param Manager $profileManager
     * @param StoreManagerInterface $storeManager
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
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->storeManager = $storeManager;
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
        $data['change_status_popup']['disableCheckbox'] = $this->getConfigValue($profileId) ? false : true;

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $profileId = $this->dataPersistor->get('subscription_id');
        $message = $this->getConfigValue($profileId) ? '' : __('Notifications are disabled in the config.');
        return [
            'general' => [
                'children' => [
                    'comment_notify' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'additionalInfo' => $message
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config value
     *
     * @param $profileId
     * @return mixed
     */
    private function getConfigValue($profileId)
    {
        $profile = $this->profileManager->loadProfile($profileId);
        if ($profile->getStoreId()) {
            $storeId = $profile->getStoreId();
        } else {
            $websiteId = $this->profileManager->loadProfile($profileId)->getWebsiteId();
            $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        }
        return $this->scopeConfig->getValue(
            EmailNotifier::XML_PATH_ENABLE_COMMENT_ADDED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
