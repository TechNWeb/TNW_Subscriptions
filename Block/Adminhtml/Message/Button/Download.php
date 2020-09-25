<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\Message\Button;

use Magento\Backend\Block\Widget\Button\SplitButton;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class Download - Adminhtml for message download button
 */
class Download implements ButtonProviderInterface
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * Generic constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->fileSystem = $context->getFileSystem();
    }

    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        return [
            'name' => 'download',
            'label' => __('Download File'),
            'class_name' => SplitButton::class,
            'options' => $this->getOptions(),
        ];
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        $directoryRead = $this->fileSystem->getDirectoryRead(DirectoryList::LOG);
        return array_map(function ($fileName) use ($directoryRead) {
            $relativePath = $directoryRead->getRelativePath($fileName);
            $urlDownload = $this->urlBuilder->getUrl('tnw_subscriptions/message/download', [
                'fileName'=>$relativePath
            ]);

            return [
                'label' => $relativePath,
                'onclick' => "setLocation('{$urlDownload}')",
                'default' => strcasecmp($relativePath, 'tnw_subscriptions.log') === 0,
            ];
        }, $directoryRead->search('*'));
    }
}
