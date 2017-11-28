<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription\Products;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use TNW\Subscriptions\Model\Processor\Response as ResponseProcessor;
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
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $errors = [];
        $request = $this->getRequest()->getParams();
        $subProduct = $this->getSubProduct($request);

        if ($subProduct) {
            try {
                $this->currentProfile = $this->configurableManager->processProfileUpdade($request);
                if (is_string($this->currentProfile)) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($this->currentProfile));
                }
                if ($this->currentProfile && $this->currentProfile->hasDataChanges()) {
                    $this->currentProfile->setNeedRecollect('1');
                }
                $this->profileManager->saveProfile();
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        } else {
            $errors[] = __('Subscription profile item wasn\'t loaded');
        }

        $response = $this->getJsonResponse($errors);

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)
            ->setData($response);
    }

    /**
     * Return subscription product.
     *
     * @param array $request
     * @return bool|\TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
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
     * Return response array.
     *
     * @param array $errors
     * @return array
     */
    private function getJsonResponse(array $errors)
    {
        $response = ['data' => [], 'error' => false];
        if (!empty($errors)) {
            $errorMessages = [];
            foreach ($errors as $error) {
                if (!empty($error)) {
                    if (is_array($error)) {
                        $errorMessages[] = reset($error);
                    } else {
                        $errorMessages[] = $error;
                    }
                }
            }
            $response = [
                'error_messages' => $errorMessages,
                'error' => true,
            ];
        } else {
            $response['redirectUrl'] = $this->getRedirectUrl();
        }

        return $response;
    }

    /**
     * Return redirect url.
     *
     * @return string
     */
    private function getRedirectUrl()
    {
        if ($this->currentProfile) {
            return $this->_url->getUrl(
                'tnw_subscriptions/subscription/items',
                ['entity_id' => $this->currentProfile->getId()]
            );
        }
    }
}
