<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Attribute\Edit\Tab;

use Magento\Eav\Block\Adminhtml\Attribute\Edit\Main\AbstractMain;

/**
 * Subscription profile attribute add/edit form main tab
 */
class Main extends AbstractMain
{
    /**
     * Adding subscription profile form elements for editing attribute.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();

        /** @var \Magento\Eav\Model\Attribute $attributeObject */
        $attributeObject = $this->getAttributeObject();
        /** @var $form \Magento\Framework\Data\Form */
        $form = $this->getForm();
        /** @var $fieldset \Magento\Framework\Data\Form\Element\Fieldset */
        $fieldset = $form->getElement('base_fieldset');

        $fiedsToRemove = [
            'attribute_code',
            'is_unique',
            'frontend_class'
        ];

        foreach ($fieldset->getElements() as $element) {
            /** @var \Magento\Framework\Data\Form\Element\AbstractElement $element  */
            if (strpos($element->getId(), 'default_value') !== 0) {
                continue;
            }

            $fiedsToRemove[] = $element->getId();
        }

        foreach ($fiedsToRemove as $id) {
            $fieldset->removeField($id);
        }

        $additionalTypes = [];
        if (strcasecmp('gallery', $attributeObject->getFrontendInput()) === 0) {
            $additionalTypes[] = [
                'value' => $attributeObject->getFrontendInput(),
                'label' => __('Gallery'),
            ];
        }

        $this->_coreRegistry->register('attribute_type_hidden_fields', []);

        $frontendInputElm = $form->getElement('frontend_input');
        $frontendInputValues = array_merge($frontendInputElm->getValues(), $additionalTypes);
        $frontendInputElm->setValues($frontendInputValues);

        return $this;
    }
}
