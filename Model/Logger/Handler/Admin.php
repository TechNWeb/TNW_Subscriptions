<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Logger\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

/**
 * Class Admin - admin logger
 */
class Admin extends AbstractProcessingHandler
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Message constructor.
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->messageManager = $messageManager;
        $this->appState = $appState;
        $this->request = $request;
        parent::__construct(\Monolog\Logger::INFO);
    }

    protected function write(LogRecord $record): void
    {
        $recordArray = $record->toArray();
        if ($this->appState->getAreaCode()
            && strcasecmp($this->appState->getAreaCode(), \Magento\Framework\App\Area::AREA_ADMINHTML) !== 0
        ) {
            return;
        }

        if ($this->request->getActionName()
            && strcasecmp($this->request->getActionName(), 'inlineEdit') === 0
        ) {
            return;
        }

        switch ($recordArray['level']) {
            case \Monolog\Logger::ERROR:
                $this->messageManager->addErrorMessage($recordArray['message'], 'backend');
                break;

            case \Monolog\Logger::WARNING:
                $this->messageManager->addWarningMessage($recordArray['message'], 'backend');
                break;

            case \Monolog\Logger::INFO:
                $this->messageManager->addSuccessMessage($recordArray['message'], 'backend');
                break;

            case \Monolog\Logger::NOTICE:
                $this->messageManager->addNoticeMessage($recordArray['message'], 'backend');
                break;
        }
    }
}
