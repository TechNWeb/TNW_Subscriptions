<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\PaymentMethods;

use Magento\Framework\Filesystem;
use Magento\Framework\Component\ComponentRegistrar;

/**
 * Class ActiveMethods - as specific to TNW_Stripe payment method dependency functionality
 */
class ActiveMethods
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * ActiveMethods constructor.
     * @param Filesystem $filesystem
     * @param ComponentRegistrar $componentRegistrar
     */
    public function __construct(
        Filesystem $filesystem,
        ComponentRegistrar $componentRegistrar
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->filesystem = $filesystem;
    }

    /**
     * @param $subject
     * @param $result
     * @param $method
     * @return mixed
     */
    public function afterGetFieldConfig($subject, $result, $method)
    {
        if ($method['code'] == 'tnw_stripe' && $this->getComposerDataVersion() < '2.3.17') {
            $result['comment'] = __("Please update TNW_Stripe Module to version 2.3.17 or above.");
            $result['value'] = 0;
            $result['disabled'] = true;
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getComposerDataVersion()
    {
        $version = '2.3.17';
        $path = $this->componentRegistrar->getPath(
            ComponentRegistrar::MODULE,
            'TNW_Stripe'
        );
        if (!$path) {
            return $version;
        }
        try {
            $json = $this->filesystem->getDirectoryReadByPath($path)->readFile('composer.json');
            $data = \Zend_Json::decode($json);
            if (array_key_exists('version', $data)) {
                $version = $data['version'];
            }
        } catch (\Throwable $e) {
            $version = '0.0.0';
        }

        return $version;
    }
}
