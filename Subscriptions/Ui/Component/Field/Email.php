<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Field;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Class for email field in Subscription Profile creating page.
 */
class Email extends \Magento\Ui\Component\Form\Field
{
    /**
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param QuoteSessionInterface $session
     * @param UrlInterface $url
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        QuoteSessionInterface $session,
        UrlInterface $url,
        $components = [],
        array $data = []
    ) {
        $this->session = $session;
        $this->url = $url;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $customerId = $this->session->getCustomerId();

        $config = $this->getData('config');
        if ($customerId) {
            $config['disabled'] = true;
            $this->setData('config', $config);
        } else {
            $config['imports'] = ['checkEmailUrl' => $this->getCheckEmailUrl()];
            $config['validation']['required-entry'] = true;
            $this->setData('config', $config);
        }

        parent::prepare();
    }

    /**
     * Get Url for email checking.
     *
     * @return string
     */
    private function getCheckEmailUrl()
    {
        return $this->url->getUrl('tnw_subscriptions/subscriptionprofile/checkcustomeremail');
    }
}
