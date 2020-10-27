<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Block\System\Config\Form\Field\Extension;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleList;

class Version extends Field
{
    protected $moduleList;

    public function __construct(
        Context $context,
        ModuleList $moduleList,
        array $data = []
    ) {
        $this->moduleList = $moduleList;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setReadonly(1);
        $module = $this->moduleList->getOne('TNW_Subscriptions');
        if ($module && isset($module['setup_version'])) {
            $element->setValue($module['setup_version']);
        }

        return $element->getElementHtml();
    }

}
