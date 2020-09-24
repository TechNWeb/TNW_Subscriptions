<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\Customer\Tab;

use Magento\Customer\Controller\RegistryConstants;

/**
 * Adminhtml customer orders grid block
 *
 * @api
 * @since 100.0.2
 */
class Profiles extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Sales reorder
     *
     * @var \Magento\Sales\Helper\Reorder
     */
    protected $_salesReorder = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var  \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Status\Options
     */
    protected $profileStatusOption;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $collectionFactory
     * @param \Magento\Sales\Helper\Reorder $salesReorder
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $collectionFactory,
        \Magento\Sales\Helper\Reorder $salesReorder,
        \Magento\Framework\Registry $coreRegistry,
        \TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Status\Options $profileStatusOption,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_salesReorder = $salesReorder;
        $this->_collectionFactory = $collectionFactory;
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
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->getReport('tnw_subscriptionprofile_grid_data_source')->addFieldToSelect(
            'entity_id'
        )->addFieldToSelect(
            'customer_id'
        )->addFieldToSelect(
            'created_at'
        );

        $collection->addFieldToFilter(
            'main_table.customer_id',
            $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID)
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
            ['header' => __('Label'), 'index' => 'label']
        );

        $this->addColumn(
            'frequency_label',
            ['header' => __('Billing Frequency'), 'index' => 'frequency_label']
        );

        $this->addColumn(
            'start_date',
            ['header' => __('Trial End Date'), 'index' => 'start_date', 'type' => 'date']
        );

        $this->addColumn(
            'next_billing_cycle_date',
            ['header' => __('Next Bill Date'), 'index' => 'next_billing_cycle_date', 'type' => 'date']
        );

        $this->addColumn(
            'grand_total',
            ['header' => __('Next Payment'), 'index' => 'grand_total', 'type' => 'currency']
        );

        $this->addColumn(
            'current_value',
            ['header' => __('Current Value'), 'index' => 'current_value', 'type' => 'currency', 'sortable' => false]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'source' =>\TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile\Status\Options::class,
                'options' => $this->profileStatusOption->getAllOptions()
            ]
        );

        $this->addColumn(
            'created_at',
            ['header' => __('Purchased'), 'index' => 'created_at', 'type' => 'date']
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
