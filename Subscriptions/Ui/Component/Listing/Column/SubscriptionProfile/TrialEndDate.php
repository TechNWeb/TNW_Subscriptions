<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Listing\Column\SubscriptionProfile;

use Magento\Ui\Component\Listing\Columns\Date;

class TrialEndDate extends Date
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$this->getData('name')])) {
                    $date = $this->timezone->date(new \DateTime($item[$this->getData('name')]));
                    if (isset($this->getConfiguration()['timezone']) && !$this->getConfiguration()['timezone']) {
                        $date = new \DateTime($item[$this->getData('name')]);
                    }
                    $item[$this->getData('name')] = $date->format('Y-m-d H:i:s');
                    if (empty($item['trial_start_date'])){
                        $item[$this->getData('name')] = '';
                    }
                }
            }
        }

        return $dataSource;
    }
}