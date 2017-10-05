<?php
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResponseInterface;

class AdditionalSave extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    private $layoutFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $layout = $this->layoutFactory->create();
        $layout->initMessages();

        $response = ['error' => true];
        $response['messages'] = ['asdasd'];
        $response['params'] = [];

        return $this->resultFactory
            ->create(ResultFactory::TYPE_JSON)
            ->setData($response);
    }
}