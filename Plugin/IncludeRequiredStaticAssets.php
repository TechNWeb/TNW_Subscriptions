<?php

namespace TNW\Subscriptions\Plugin;

use Magento\Framework\Module\Manager;
use Magento\Framework\RequireJs\Config as RequirejsConfig;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\File;
use Magento\Framework\View\File\Collector\Base;
use Magento\Framework\View\File\Collector\Decorator\ModuleOutput;

/**
 * Plugin is including requirejs-config.js and _module.less files during static assets generation
 * even if module is turned off for all websites & globally (to overcome module_disable_output)
 */
class IncludeRequiredStaticAssets
{
    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var Base
     */
    private $baseCollector;

    /**
     * IncludeRequiredStaticAssets constructor.
     * @param Manager $moduleManager
     * @param Base $baseCollector
     */
    public function __construct(
        Manager $moduleManager,
        Base $baseCollector
    ) {
        $this->moduleManager = $moduleManager;
        $this->baseCollector = $baseCollector;
    }

    /**
     * @param ModuleOutput $subject
     * @param callable $proceed
     * @param ThemeInterface $theme
     * @param string $filePath
     * @return File[]
     */
    public function aroundGetFiles(
        ModuleOutput $subject,
        callable $proceed,
        ThemeInterface $theme,
        $filePath
    ) {
        $result = $proceed($theme, $filePath);
        $isModuleLess = (strpos($filePath, 'css/source/_module.less') !== false);
        if ($isModuleLess || strpos($filePath, RequirejsConfig::CONFIG_FILE_NAME) !== false) {
            $filePath = $isModuleLess ? 'web/' . $filePath : $filePath;
            foreach ($this->baseCollector->getFiles($theme, $filePath) as $file) {
                if ($file->getModule() === 'TNW_Subscriptions'
                    && !$this->moduleManager->isOutputEnabled($file->getModule())
                ) {
                    $result[] = $file;
                }
            }
        }
        return $result;
    }
}
