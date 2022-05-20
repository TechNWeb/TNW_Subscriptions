<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\Customer\Tab;

use Magento\Customer\Controller\RegistryConstants;

/**
 * Adminhtml customer profiles grid block
 */
class Profiles extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var  \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Status\Options
     */
    protected $profileStatusOption;

    /**
     * Profiles constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Status\Options $profileStatusOption
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Status\Options $profileStatusOption,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->collectionFactory = $collectionFactory;
        $this->profileStatusOption = $profileStatusOption;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customer_profiles_grid');
        $this->setDefaultSort('main_table.created_at', 'desc');
        $this->setUseAjax(true);
    }

    /**
     * Apply various selection filters to prepare the sales order grid collection.
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     * @throws \Exception
     */
    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->getReport('tnw_subscriptionprofile_grid_data_source')->addFieldToSelect(
            'entity_id'
        )->addFieldToSelect(
            'customer_id'
        )->addFieldToSelect(
            'created_at'
        );

        $collection->addFieldToFilter(
            'main_table.customer_id',
            $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID)
        );

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {

        $this->addColumn(
            'label',
            [
                'header' => __('Label'),
                'index' => 'label',
                'filter_index' => 'main_table.entity_id'
            ]
        );

        $this->addColumn(
            'frequency_label',
            [
                'header' => __('Billing Frequency'),
                'index' => 'frequency_label',
                'filter_index' => 'frequency.label'
            ]
        );

        $this->addColumn(
            'next_billing_cycle_date',
            [
                'header' => __('Next Bill Date'),
                'index' => 'next_billing_cycle_date',
                'type' => 'date',
                'filter_index' => 'relation.scheduled_at'
            ]
        );

        $this->addColumn(
            'grand_total',
            ['header' => __('Next Payment'), 'index' => 'grand_total', 'type' => 'currency']
        );

        $this->addColumn(
            'current_value',
            [
                'header' => __('Current Value'),
                'index' => 'current_value',
                'type' => 'currency',
                'sortable' => false,
                'filter_index' => 'profit.total_profit'
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'source' =>\TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Status\Options::class,
                'options' => $this->profileStatusOption->getAllOptions(),
                'filter_index' => 'main_table.status'
            ]
        );

        $this->addColumn(
            'created_at',
            [
                'header' => __('Purchased'),
                'index' => 'created_at',
                'type' => 'date',
                'filter_index' => 'main_table.created_at'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Retrieve the Url for a specified sales order row.
     *
     * @param \Magento\Sales\Model\Order|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('tnw_subscriptions/subscriptionprofile/edit', ['entity_id' => $row->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getGridUrl()
    {
        return $this->getUrl('tnw_subscriptions/customer/profiles', ['_current' => true]);
    }
}
