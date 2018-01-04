<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription\Products;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\ItemFactory;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\Context as ContextModel;
use TNW\Subscriptions\Model\ProductSubscriptionProfileRepository;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Configure product's options in saved subscription.
 */
class Edit extends \Magento\Framework\App\Action\Action
    implements \Magento\Catalog\Controller\Product\View\ViewInterface
{
    /**
     * @var ProductSubscriptionProfileRepository
     */
    private $productSubscriptionRepository;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * Core registry
     *
     * @var Registry
     */
    private $coreRegistry;

    /**
     * Subscription profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ContextModel
     */
    private $contextModel;

    /**
     * @param Context $context
     * @param ProductSubscriptionProfileRepository $productSubscriptionRepository
     * @param DataPersistorInterface $dataPersistor
     * @param Registry $registry
     * @param ProfileManager $profileManager
     * @param LoggerInterface $logger
     * @param ContextModel $contextModel
     */
    public function __construct(
        Context $context,
        ProductSubscriptionProfileRepository $productSubscriptionRepository,
        DataPersistorInterface $dataPersistor,
        Registry $registry,
        ProfileManager $profileManager,
        LoggerInterface $logger,
        ContextModel $contextModel
    ) {
        parent::__construct($context);
        $this->productSubscriptionRepository = $productSubscriptionRepository;
        $this->dataPersistor = $dataPersistor;
        $this->coreRegistry = $registry;
        $this->profileManager = $profileManager;
        $this->logger = $logger;
        $this->contextModel = $contextModel;
    }

    /**
     * Action to reconfigure subscriptions item
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Extract subscription profile and product to configure
        $subscriprionProductId = (int)$this->getRequest()->getParam('id');
        $productId = (int)$this->getRequest()->getParam('product_id');
        $subscriprionProduct = null;
        if ($subscriprionProductId) {
            $subscriprionProduct = $this->productSubscriptionRepository->getById($subscriprionProductId);
            $subscriptionProfile = $this->profileManager->loadProfile($subscriprionProduct->getSubscriptionProfileId());
        } else {
            $this->messageManager->addError(
                __('Product with ID %1 could not be found. Cannot Edit the product on the Subscription Profile.', $productId)
            );
            return $this->goBack('customer/account');
        }

        try {
            $this->coreRegistry->register('tnw_subscription_product', $subscriprionProduct);
            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $this->_objectManager->get(\Magento\Catalog\Helper\Product\View::class)
                ->prepareAndRender(
                    $resultPage,
                    $productId,
                    $this,
                    $this->getProductParams($subscriprionProduct)
                );

            return $resultPage;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(__('We cannot configure the product.'));
            $this->logger->critical($e);
            if ($subscriptionProfile->getId()) {
                return $this->goBack('tnw_subscriptions/subscription/items', $subscriptionProfile->getId());
            } else {
                return $this->goBack('customer/account/');
            }
        }
    }

    /**
     * Set back redirect url to response.
     *
     * @param string $path
     * @param int $subscriptionId
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function goBack($path, $subscriptionId = 0)
    {
        return $this->resultFactory
            ->create(ResultFactory::TYPE_REDIRECT)
            ->setPath(
                $path,
                [
                    'entity_id' => $subscriptionId,
                ]
            );
    }

    /**
     * Return custom data from subscription product.
     *
     * @param ProductSubscriptionProfileInterface $subscriprionProduct
     * @return \Magento\Framework\DataObject
     */
    private function getProductParams(ProductSubscriptionProfileInterface $subscriprionProduct)
    {
        $params = new \Magento\Framework\DataObject();
        $customData = $subscriprionProduct->getCustomOptions();

        if ($this->contextModel->isJson($customData)) {
            $buyRequest = new \Magento\Framework\DataObject();
            $customData = \Zend_Json::decode($customData);
            $buyRequest->setSuperAttribute($customData);
            $params->setBuyRequest($buyRequest);
        }

        return $params;
    }
}
