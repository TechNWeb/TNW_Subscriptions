<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\SubscriptionProfile;

use Magento\Ui\Component\Form as BaseForm;

/**
 * Class for UI subscription profile form.
 */
class Form extends BaseForm
{
    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        $this->getContext()->addComponentDefinition(
            'nav',
            [
                'component' => 'TNW_Subscriptions/js/form/element/sub-edit-tab_group',
                'config' => [
                    'template' => 'TNW_Subscriptions/form/element/sub-edit-tab'
                ]
            ]
        );

        $this->getContext()->addComponentDefinition(
            'tab',
            [
                'component' => 'TNW_Subscriptions/js/form/element/sub-edit-area',
                'config' => [
                    'template' => 'TNW_Subscriptions/form/subscription-profile/edit/area-with-message_container'
                ]
            ]
        );

        return parent::prepareDataSource($dataSource);
    }
}