<?php

namespace TNW\Subscriptions\Controller\Subscription\MassEdit;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\SerializerInterface;

class Save extends Action
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
            'mass_edit_result' =>
                [
                    'message' => 'Subscription profiles changed successfully',
                    'error' => false
                ],
            'error' => false, //error string if error
            'billing_address_summary' => 'Billing Canada, <br>12345 Len oir, Montreal, <br>Alberta h1h1h1, Canada',
            'payment_summary' =>
                '<div class="block">
                    <div class="block-content inner-block tnw_subscriptions">
                    <table class="table-secondary data-table">
                        <tbody>
                        <tr class="col-0">
                            <td class="label">Payment Type</td>
                            <td class="right">Credit Card (Braintree)</td>
                        </tr>
                                                            <tr class="col-1">
                                    <td class="label">Card Type</td>
                                    <td class="right"><span class="text">Visa</span></td>
                                </tr>
                                <tr class="col-2">
                                    <td class="label">Card Number</td>
                                    <td class="right"><span class="text">XXXX1111</span></td>
                                </tr>
                                <tr class="col-3">
                                    <td class="label">Exp. Date</td>
                                    <td class="right"><span class="text">12/2023</span></td>
                                </tr>
                                                                    <tr class="col-4">
                            <td class="label">Currency</td>
                            <td class="right"><span class="text">USD</span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            '
        ];

//        if ($this->getRequest()->getParam('isAjax', false)) {
            return $this->resultJsonFactory->create()->setJsonData($this->serializer->serialize($response));
//        }

//        throw new NotFoundException(__('Not found'));
    }
}
