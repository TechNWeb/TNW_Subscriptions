<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Attribute;

use Magento\Eav\Block\Adminhtml\Attribute\Grid\AbstractGrid;

/**
 * Subscription profile attributes grid.
 */
class Grid extends AbstractGrid
{
    /**
     * EAV entity type model.
     *
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $eavEntityType;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Eav\Model\Entity\Type $eavEntityType
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Eav\Model\Entity\Type $eavEntityType,
        array $data = []
    ) {
        $this->eavEntityType = $eavEntityType;
        $this->_module = 'tnw_subscriptions';
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * \Magento\Eav\Model\Entity\Type::getAttributeCollection
     *
     * @return AbstractGrid
     */
    protected function _prepareCollection()
    {
        $this->eavEntityType->loadByCode(\TNW\Subscriptions\Model\SubscriptionProfile::ENTITY);
        $collection = $this->eavEntityType->getAttributeCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }
}
