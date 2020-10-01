<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription\Products;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Catalog\Controller\Product\View\ViewInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfileRepository;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Configure product's options in saved subscription.
 */
class Edit extends Action implements ViewInterface
{
    /**
     * @var ProductSubscriptionProfileRepository
     */
    private $productSubscriptionRepository;

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
     * @param Context $context
     * @param ProductSubscriptionProfileRepository $productSubscriptionRepository
     * @param Registry $registry
     * @param ProfileManager $profileManager
     */
    public function __construct(
        Context $context,
        ProductSubscriptionProfileRepository $productSubscriptionRepository,
        Registry $registry,
        ProfileManager $profileManager
    ) {
        parent::__construct($context);
        $this->productSubscriptionRepository = $productSubscriptionRepository;
        $this->coreRegistry = $registry;
        $this->profileManager = $profileManager;
    }

    /**
     * Action to reconfigure subscriptions item
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        // Extract subscription profile and product to configure
        $subscriprionProductId = (int)$this->getRequest()->getParam('id');
        $productId = (int)$this->getRequest()->getParam('product_id');

        try {
            $subscriprionProduct = $this->productSubscriptionRepository->getById($subscriprionProductId);
            $subscriptionProfile = $this->profileManager->loadProfile($subscriprionProduct->getSubscriptionProfileId());
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __(
                    'Product with ID %1 could not be found. Cannot Edit the product on the Subscription Profile.',
                    $productId
                )
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
            $this->messageManager->addExceptionMessage($e, __('We cannot configure the product.'));
            if ($subscriptionProfile->getId()) {
                return $this->goBack('tnw_subscriptions/subscription/items', $subscriptionProfile->getId());
            }

            return $this->goBack('customer/account/');
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

        if (!empty($customData['info_buyRequest'])) {
            $buyRequest = new \Magento\Framework\DataObject($customData['info_buyRequest']);
            $params->setBuyRequest($buyRequest);
        }

        return $params;
    }
}
