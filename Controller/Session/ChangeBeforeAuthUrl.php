<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Session;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Change before auth url to redirect after login.
 */
class ChangeBeforeAuthUrl extends Action
{
    /**
     * Customer session.
     *
     * @var Session
     */
    private $session;

    /**
     * Json factory.
     *
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param Context $context
     * @param Session $session
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        Session $session,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);

        $this->session = $session;
        $this->resultJsonFactory = $jsonFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $response->setData('error', false);

        try {
            $routePath = $this->_request->getParam('route_path');
            $hash = $this->_request->getParam('hash');

            $url = $this->_url->getUrl($routePath);

            $this->session->setBeforeAuthUrl($url . $hash);
        } catch (\Exception $e) {
            $response->setData('error', true);
            $response->setData('data', $e);
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
