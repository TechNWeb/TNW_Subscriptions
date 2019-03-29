<?php
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Edit\Product;

use Magento\Framework\App\ResponseInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use Magento\Backend\App\Action;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use TNW\Subscriptions\Model\ProductSubscriptionProfileFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager\Configurable as ConfigurableTypeManager;

class Add extends Action
{
    /**
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * @var ProductSubscriptionProfileFactory
     */
    private $productProfileFactory;

    /**
     * @var Product
     */
    private $productModifier;

    /**
     * @var ConfigurableTypeManager
     */
    private $configurableTypeManager;

    /**
     * Add constructor.
     * @param Action\Context $context
     * @param ProfileManager $profileManager
     * @param ProductSubscriptionProfileFactory $productProfileFactory
     * @param Product $productModifier
     * @param ConfigurableTypeManager $configurableTypeManager
     */
    public function __construct(
        Action\Context $context,
        ProfileManager $profileManager,
        ProductSubscriptionProfileFactory $productProfileFactory,
        Product $productModifier,
        ConfigurableTypeManager $configurableTypeManager
    ) {
        parent::__construct($context);
        $this->profileManager = $profileManager;
        $this->productProfileFactory = $productProfileFactory;
        $this->productModifier = $productModifier;
        $this->configurableTypeManager = $configurableTypeManager;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        if (null === $profile) {
            // Error
            return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
                ->setData([]);
        }

        $request = $this->getRequest()->getParams();
        $request['billing_frequency'] = $profile->getBillingFrequencyId();
        if (isset($request['subscribe_qty'])) {
            $request['qty'] = $request['subscribe_qty'];
        }

        $this->productModifier->reset();
        $this->productModifier->setData($request);
        $product = $this->productModifier->getProduct();
        $requestData = $this->productModifier->getPreparedBuyRequest();

        /** @var \Magento\Catalog\Model\Product[] $cartCandidates */
        $cartCandidates = $product->getTypeInstance()->prepareForCartAdvanced(
            $requestData,
            $product
        );

        /**
         * Error message
         */
        if (is_string($cartCandidates) || $cartCandidates instanceof \Magento\Framework\Phrase) {
            // Error
            return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
                ->setData([]);
        }

        /**
         * If prepare process return one object
         */
        if (!is_array($cartCandidates)) {
            $cartCandidates = [$cartCandidates];
        }

        $price = $this->configurableTypeManager->getSubscriptionPrice($product, $requestData);

        $profileProducts = $profile->getProducts();

        $parentItem = null;
        foreach ($cartCandidates as $candidate) {
            // Child items can be sticked together only within their parent
            $stickWithinParent = $candidate->getParentProductId() ? $parentItem : null;
            $candidate->setStickWithinParent($stickWithinParent);

            $item = $this->productProfileFactory->create()
                ->setDataChanges(false)
                ->setQty($candidate->getQty())
                ->setPrice($price);

            $options = $candidate->getTypeInstance()->getOrderOptions($candidate);
            unset($options['info_buyRequest']['subscription_data']);
            $item->setCustomOptions($options);

            /**
             * As parent item we should always use the item of first added product
             */
            if (!$parentItem) {
                $parentItem = $item;
            }
            if ($parentItem && $candidate->getParentProductId() && !$item->getParentId()) {
                $item->setParentId($parentItem->getId());
            }

            $profileProducts[] = $item;
        }

        $profile->setProducts($profileProducts);
        $profile->setDataChanges(true);
        $this->profileManager->saveProfile();

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData([]);
    }
}
