<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\ProductSubscriptionProfile\Attribute;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Eav\Block\Adminhtml\Attribute\Grid\AbstractGrid;
use Magento\Eav\Model\Entity\Type;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;

/**
 * Profile product attributes grid.
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Grid extends AbstractGrid
{
    /**
     * @var Type
     */
    private $eavEntityType;

    /**
     * Grid constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param Type $eavEntityType
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Type $eavEntityType,
        array $data = []
    ) {
        $this->_module = 'tnw_subscriptions';
        $this->eavEntityType = $eavEntityType;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareCollection()
    {
        $this->eavEntityType->loadByCode(ProductSubscriptionProfile::ENTITY);
        $collection = $this->eavEntityType->getAttributeCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }
}

