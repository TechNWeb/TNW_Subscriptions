<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription\Queue;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\ReBillRepository;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Class Process - used to submit the re-bill from
 */
class ProcessPost extends AbstractAccount implements HttpPostActionInterface
{
    /**
     * @var ReBillRepository
     */
    private $reBillRepository;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * Process constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ReBillRepository $reBillRepository
     * @param CustomerSession $customerSession
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ReBillRepository $reBillRepository,
        CustomerSession $customerSession,
        ManagerInterface $messageManager
    ) {
        $this->customerSession = $customerSession;
        $this->reBillRepository = $reBillRepository;
        $this->jsonFactory = $jsonFactory;
        $this->messageManager = $messageManager;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $nonce = $this->getRequest()->getParam('paymentMethodNonce');

        $messages[] = __('The order was successfully re-billed.');

        foreach ($messages as $message) {
            if ($error) {
                $this->messageManager->addErrorMessage($message);
            } else {
                $this->messageManager->addSuccessMessage($message);
            }
        }
        return $resultJson->setData(
            [
                'messages' => $messages,
                'error' => $error
            ]
        );
    }
}
