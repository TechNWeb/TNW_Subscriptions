<?php

namespace TNW\Subscriptions\Controller\Subscription\MassEdit;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\SerializerInterface;

class ProcessShipping extends Action
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * MassEdit constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SerializerInterface $serializer
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serializer = $serializer;
    }


    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $response = [
            'error' => false, //error string if error
            'shipping_methods' => [
                [
                    'value' => 'flat_rate',
                    'label' => 'Flat Rate'
                ],
                [
                    'value' => 'fedex',
                    'label' => 'Fedex Ground'
                ]
            ],
            'shipping_address_summary' => 'Some Canada, <br>12345 Len oir, Montreal, <br>Alberta h1h1h1, Canada'
        ];

        if ($this->getRequest()->getParam('isAjax', false)) {
            return $this->resultJsonFactory->create()->setJsonData($this->serializer->serialize($response));
        }

        throw new NotFoundException(__('Not found'));
    }
}
