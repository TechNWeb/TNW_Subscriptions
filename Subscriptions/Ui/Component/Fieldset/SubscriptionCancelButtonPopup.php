<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Fieldset;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Framework\App\Request\DataPersistorInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Fieldset for cancel button popup.
 */
class SubscriptionCancelButtonPopup extends Fieldset
{
    /**
     * Data Persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @param ContextInterface $context
     * @param DataPersistorInterface $dataPersistor
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        DataPersistorInterface $dataPersistor,
        $components = [],
        array $data = []
    ) {
        $this->dataPersistor = $dataPersistor;

        parent::__construct($context, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        parent::prepare();

        $subscriptionId = $this->dataPersistor->get('subscription_id');

        $config = $this->getData('config');
        $config['imports'] = [
            'subscriptionNumber' => SubscriptionProfileInterface::LABEL_PREFIX . $subscriptionId,
        ];

        $this->setData('config', (array)$config);
    }
}
