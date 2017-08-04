<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\Component\Field;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Form\Field;

/**
 * Form question in customer exists popup.
 */
class CustomerExistsQuestion extends Field
{
    /**
     * Data Persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        DataPersistorInterface $dataPersistor,
        $components = [],
        array $data = []
    ) {
        $this->dataPersistor = $dataPersistor;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        parent::prepare();

        $customerName = $this->dataPersistor->get('existsCustomerName');
        $customerEmail = $this->dataPersistor->get('existsCustomerEmail');

        $customerData = [
            'imports' => [
                'customerEmail' => $customerEmail,
                'customerName' => $customerName,
            ]
        ];

        $this->wrappedComponent->setData(
            'config',
            array_replace_recursive(
                $customerData,
                (array) $this->wrappedComponent->getData('config')
            )
        );

        $this->setData(
            'config',
            array_replace_recursive(
                $customerData,
                (array) $this->getData('config')
            )
        );
    }
}
