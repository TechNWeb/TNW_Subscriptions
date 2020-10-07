<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\Level;

use Magento\Framework\Data\OptionSourceInterface;
use Monolog\Logger;

/**
 * Class Options - ui
 */
class Options implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $option = [];
        foreach (Logger::getLevels() as $name => $level) {
            $option[] = ['value' => $level, 'label' => $name];
        }

        return $option;
    }
}
