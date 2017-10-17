<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Request\Save\Profile;

/**
 * Save modified products processor.
 */
class ModifiedProducts extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $saveModel = $this->getSubCreateModel();
        $objectId = $this->getFieldValue($data, 'objectId', false);
        if ($objectId) {
            $objectItemId = $this->getFieldValue($data, 'objectItemId', false);
            if ($objectItemId) {
                $remove = $this->getFieldValue($data, 'remove', false);
                $requestData = $this->getFieldValue($data, 'item_' . $objectItemId, false);
                $request = $saveModel->removeSubscriptions($requestData, $objectId, $objectItemId);
                if (!$remove) {
                    $saveModel->addToSubscription($request);
                }
            } else {
                $this->errors[] = __('Object item id is not defined.');
            }
        } else {
            $this->errors[] = __('Object id is not defined.');
        }
    }
}