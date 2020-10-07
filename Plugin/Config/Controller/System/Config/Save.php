<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Config\Controller\System\Config;

/**
 * Class Save - plugin for Config save execute action
 */
class Save
{
    /**
     * @var \TNW\Subscriptions\Plugin\Config\Model\Structure
     */
    private $structure;

    /**
     * Save constructor.
     *
     * @param \TNW\Subscriptions\Plugin\Config\Model\Structure $structure
     */
    public function __construct(
        \TNW\Subscriptions\Plugin\Config\Model\Structure $structure
    ) {
        $this->structure = $structure;
    }

    /**
     * @param $subject
     */
    public function beforeExecute($subject)
    {
        $groups = $subject->getRequest()->getPost('groups');
        $section = $subject->getRequest()->getParam('section');
        if ($section == 'tnw_subscriptions_payment_methods') {
            foreach ($groups as $groupName => $fields) {
                if ($groupName == 'active_methods') {
                    if (array_key_exists('fields', $fields) && is_array($fields['fields'])) {
                        foreach ($fields['fields'] as $fieldName => $valueData) {
                            $this->structure->addDynamicConfigPath($section . '/' . $groupName . '/' . $fieldName);
                        }
                    }
                }
            }
        }
    }
}
