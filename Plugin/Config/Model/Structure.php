<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Config\Model;

/**
 * Class Structure
 * @package TNW\Subscriptions\Plugin\Config\Model
 */
class Structure
{
    /**
     * @var array
     */
    private $paths = [];

    /**
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterGetFieldPaths($subject, $result)
    {
        foreach ($this->paths as $pathToInclude) {
            $result[$pathToInclude] = [$pathToInclude];
        }
        return $result;
    }

    /**
     * @param $path
     */
    public function addDynamicConfigPath($path)
    {
        $this->paths[] = $path;
    }
}
