<?php

namespace TNW\Subscriptions\Controller\Paypal;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Paypal\Model\Payflow\Transparent;
use TNW\Subscriptions\Model\Payment\Paypal\Payflowpro;

/**
 * Class for redirecting the Paypal response result to Magento controller.
 */
class Redirect implements ActionInterface, CsrfAwareActionInterface, HttpPostActionInterface
{
    /**
     * @var LayoutFactory
     */
    private $resultLayoutFactory;

    /**
     * @var Transparent
     */
    private $transparent;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Payflowpro
     */
    private $payflowpro;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Constructor
     *
     * @param LayoutFactory $resultLayoutFactory
     * @param Transparent $transparent
     * @param Logger $logger
     * @param Payflowpro $payflowpro
     * @param RequestInterface $request
     */
    public function __construct(
        LayoutFactory $resultLayoutFactory,
        Transparent $transparent,
        Logger $logger,
        Payflowpro $payflowpro,
        RequestInterface $request
    ) {
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->transparent = $transparent;
        $this->logger = $logger;
        $this->payflowpro = $payflowpro;
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Saves the payment in quote
     *
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $gatewayResponse = (array)$this->request->getPostValue();
        $this->logger->debug(
            ['PayPal PayflowPro redirect:' => $gatewayResponse],
            $this->payflowpro->getDebugReplacePrivateDataKeys(),
            $this->payflowpro->getDebugFlag()
        );

        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->addDefaultHandle();
        $resultLayout->getLayout()->getUpdate()->load(['tnw_transparent_payment_redirect']);

        return $resultLayout;
    }
}
