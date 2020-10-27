<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\Message;

/**
 * Class Truncate - action controller
 */
class Truncate extends \Magento\Backend\App\Action
{
    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\Message
     */
    protected $resourceLogger;

    /**
     * Truncate constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \TNW\Subscriptions\Model\ResourceModel\Message $resourceLogger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \TNW\Subscriptions\Model\ResourceModel\Message $resourceLogger
    ) {
        $this->resourceLogger = $resourceLogger;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $this->resourceLogger->getConnection()
                ->truncateTable($this->resourceLogger->getMainTable());
        } catch (\Exception $e) {
            $this->getMessageManager()
                ->addErrorMessage($e->getMessage(), 'backend');
        }

        return $this->resultRedirectFactory->create()
            ->setRefererUrl();
    }
}
