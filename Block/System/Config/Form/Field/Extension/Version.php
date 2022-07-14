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
use TNW\Subscriptions\Model\Config;

/**
 * Class Version - block to get the config version
 */
class Version extends Field
{
    /**
     * @var ModuleList
     */
    protected $moduleList;

    /**
     * @var Config
     */
    private $config;

    /**
     * Version constructor.
     * @param Context $context
     * @param ModuleList $moduleList
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        ModuleList $moduleList,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;
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
        if ($module) {
            $element->setValue($this->config->getComposerDataVersion());
        }

        return $element->getElementHtml();
    }
}
