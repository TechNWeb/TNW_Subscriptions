<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\InstantPurchase\Controller\Button;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\InstantPurchase\Controller\Button\PlaceOrder as PlaceOrderNative;
use Magento\InstantPurchase\Model\InstantPurchaseOptionLoadingFactory;
use Magento\InstantPurchase\Model\PlaceOrder as PlaceOrderModel;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Backend\UrlBuilder;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager;

/**
 * Class PlaceOrder - Add subscription profile label into message output
 */
class PlaceOrder
{
    /**
     * List of request params that handled by the controller
     *
     * @var array
     */
    private static $knownRequestParams = [
        'form_key',
        'product',
        'instant_purchase_payment_token',
        'instant_purchase_shipping_address',
        'instant_purchase_billing_address',
    ];

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var Manager
     */
    private $profileOrderManager;

    /**
     * @var UrlBuilder
     */
    private $profileUrlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var InstantPurchaseOptionLoadingFactory
     */
    private $instantPurchaseOptionLoadingFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var PlaceOrderModel
     */
    private $placeOrder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param ResultFactory $resultFactory
     * @param Manager $profileOrderManager
     * @param UrlBuilder $profileUrlBuilder
     * @param RequestInterface $request
     * @param MessageManagerInterface $messageManager
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param InstantPurchaseOptionLoadingFactory $instantPurchaseOptionLoadingFactory
     * @param ProductRepositoryInterface $productRepository
     * @param PlaceOrderModel $placeOrder
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ResultFactory $resultFactory,
        Manager $profileOrderManager,
        UrlBuilder $profileUrlBuilder,
        RequestInterface $request,
        MessageManagerInterface $messageManager,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        InstantPurchaseOptionLoadingFactory $instantPurchaseOptionLoadingFactory,
        ProductRepositoryInterface $productRepository,
        PlaceOrderModel $placeOrder,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->resultFactory = $resultFactory;
        $this->profileOrderManager = $profileOrderManager;
        $this->profileUrlBuilder = $profileUrlBuilder;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->formKeyValidator = $formKeyValidator;
        $this->instantPurchaseOptionLoadingFactory = $instantPurchaseOptionLoadingFactory;
        $this->productRepository = $productRepository;
        $this->placeOrder = $placeOrder;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Place an order for a customer plugin
     *
     * @param PlaceOrderNative $subject
     * @param callable $callable
     * @return JsonResult
     */
    public function aroundExecute(PlaceOrderNative $subject, callable $callable): JsonResult
    {
        if (!$this->doesRequestContainAllKnowParams($this->request)) {
            return $this->createResponse($this->createGenericErrorMessage(), false);
        }
        if (!$this->formKeyValidator->validate($this->request)) {
            return $this->createResponse($this->createGenericErrorMessage(), false);
        }

        $paymentTokenPublicHash = (string)$this->request->getParam('instant_purchase_payment_token');
        $shippingAddressId = (int)$this->request->getParam('instant_purchase_shipping_address');
        $billingAddressId = (int)$this->request->getParam('instant_purchase_billing_address');
        $carrierCode = (string)$this->request->getParam('instant_purchase_carrier');
        $shippingMethodCode = (string)$this->request->getParam('instant_purchase_shipping');
        $productId = (int)$this->request->getParam('product');
        $productRequest = $this->getRequestUnknownParams($this->request);

        try {
            $customer = $this->customerSession->getCustomer();
            $instantPurchaseOption = $this->instantPurchaseOptionLoadingFactory->create(
                $customer->getId(),
                $paymentTokenPublicHash,
                $shippingAddressId,
                $billingAddressId,
                $carrierCode,
                $shippingMethodCode
            );
            /** @var Store $store */
            $store = $this->storeManager->getStore();

            /** @var Product $product */
            $product = $this->productRepository->getById(
                $productId,
                false,
                $store->getId(),
                false
            );

            $orderId = $this->placeOrder->placeOrder(
                $store,
                $customer,
                $instantPurchaseOption,
                $product,
                $productRequest
            );
        } catch (NoSuchEntityException $e) {
            return $this->createResponse($this->createGenericErrorMessage(), false);
        } catch (Exception $e) {
            return $this->createResponse(
                $e instanceof LocalizedException ? $e->getMessage() : $this->createGenericErrorMessage(),
                false
            );
        }

        $order = $this->orderRepository->get($orderId);
        $message = __('Your order number is: %1', $order->getIncrementId());

        $profileIds = $this->profileOrderManager->getProfileIdsByOrder($orderId);
        if (count($profileIds)) {
            $profileId = array_shift($profileIds);
            $this->messageManager->addSuccessMessage(
                __('Your subscription number is: %1', $this->profileUrlBuilder->getEditLabel($profileId, $order->getStoreId()))
            );
        }

        return $this->createResponse($message, true);
    }

    /**
     * Creates error message without exposing error details
     *
     * @return string
     */
    private function createGenericErrorMessage(): string
    {
        return (string)__('Something went wrong while processing your order. Please try again later.');
    }

    /**
     * Checks if all parameters that should be handled are passed
     *
     * @param RequestInterface $request
     * @return bool
     */
    private function doesRequestContainAllKnowParams(RequestInterface $request): bool
    {
        foreach (self::$knownRequestParams as $knownRequestParam) {
            if ($request->getParam($knownRequestParam) === null) {
                return false;
            }
        }
        return true;
    }

    /**
     * Filters out parameters that handled by controller
     *
     * @param RequestInterface $request
     * @return array
     */
    private function getRequestUnknownParams(RequestInterface $request): array
    {
        $requestParams = $request->getParams();
        $unknownParams = [];
        foreach ($requestParams as $param => $value) {
            if (!isset(self::$knownRequestParams[$param])) {
                $unknownParams[$param] = $value;
            }
        }
        return $unknownParams;
    }

    /**
     * Creates response with a operation status message
     *
     * @param string $message
     * @param bool $successMessage
     * @return JsonResult
     */
    private function createResponse(string $message, bool $successMessage): JsonResult
    {
        /** @var JsonResult $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData(['response' => $message]);

        if ($successMessage) {
            $this->messageManager->addSuccessMessage($message);
        } else {
            $this->messageManager->addErrorMessage($message);
        }
        return $result;
    }
}
