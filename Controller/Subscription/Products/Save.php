<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Subscription\Products;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ManagerConfigurable;
use TNW\Subscriptions\Model\ProductSubscriptionProfileRepository;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Saves subscription profile with modified product custom options for configurable products.
 */
class Save extends Action
{
    /**
     * Subscription profile manager
     *
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * @var ManagerConfigurable
     */
    private $configurableManager;

    /**
     * @var SubscriptionProfile
     */
    private $currentProfile;

    /**
     * Subscription profile manager
     *
     * @var ProductSubscriptionProfileRepository
     */
    private $productSubscriptionRepository;

    /**
     * @param Context $context
     * @param ProfileManager $profileManager
     * @param ManagerConfigurable $configurableManager
     * @param ProductSubscriptionProfileRepository $productSubscriptionProfileRepository
     */
    public function __construct(
        Context $context,
        ProfileManager $profileManager,
        ManagerConfigurable $configurableManager,
        ProductSubscriptionProfileRepository $productSubscriptionProfileRepository
    ) {
        parent::__construct($context);
        $this->profileManager = $profileManager;
        $this->configurableManager = $configurableManager;
        $this->productSubscriptionRepository = $productSubscriptionProfileRepository;
    }

    /**
     * Execute save profile data on edit configurable product super attributes page.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $request = $this->getRequest()->getParams();

        try {
            $subProduct = $this->getSubProduct($request);
            if (empty($subProduct)) {
                throw new LocalizedException(__('Subscription profile item wasn\'t loaded'));
            }

            $this->currentProfile = $this->configurableManager->processProfileUpdate($request);

            if ($this->currentProfile instanceof SubscriptionProfile) {
                if ($this->currentProfile && $this->currentProfile->hasDataChanges()) {
                    $this->currentProfile->setNeedRecollect(true);
                }
                $this->profileManager->saveProfile();
            } elseif (is_string($this->currentProfile)) {
                throw new LocalizedException(__($this->currentProfile));
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t update the item right now.'));
            return $this->goBack();
        }

        return $this->goBack($this->getRedirectUrl());
    }

    /**
     * Set back redirect url to response
     *
     * @param null|string $backUrl
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function goBack($backUrl = null)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($backUrl || $backUrl = $this->_redirect->getRefererUrl()) {
            $resultRedirect->setUrl($backUrl);
        }

        return $resultRedirect;
    }

    /**
     * Return subscription product.
     *
     * @param array $request
     * @return bool|\TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @throws LocalizedException
     */
    private function getSubProduct(array $request)
    {
        $subProduct = false;

        $subProductId = $request['sub_product_id'];
        if ($subProductId) {
            $subProduct = $this->productSubscriptionRepository->getById($subProductId);
        }

        return $subProduct;
    }

    /**
     * Return redirect url.
     *
     * @return string|null
     */
    private function getRedirectUrl()
    {
        if ($this->currentProfile) {
            return $this->_url->getUrl(
                'tnw_subscriptions/subscription/items',
                ['entity_id' => $this->currentProfile->getId()]
            );
        }

        return null;
    }
}
