<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription\Queue;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\ReBillRepository;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Class Process - used to submit the re-bill from
 */
class ProcessPost extends AbstractAccount implements HttpPostActionInterface
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var ReBillRepository
     */
    private $reBillRepository;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Process constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ReBillRepository $reBillRepository
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ReBillRepository $reBillRepository,
        CustomerSession $customerSession
    ) {
        $this->customerSession = $customerSession;
        $this->reBillRepository = $reBillRepository;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $token = $this->getRequest()->getParam('token');
        try {
            $reBill = $this->reBillRepository->getByToken($token);
            if (!$reBill->getId() || $this->customerSession->getCustomerId() != $reBill->getCustomerId()) {
                throw new LocalizedException(__('Not Valid Data to process.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError('The provided Link is expired or invalid.');
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set('Verify and Re-Bill');
        return $resultPage;
    }
}
