<?php
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Edit\Product;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use Magento\Backend\App\Action;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use TNW\Subscriptions\Model\ProductSubscriptionProfileFactory;
use TNW\Subscriptions\Model\Product\Attribute;
use Magento\Catalog\Api\ProductRepositoryInterface;

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
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * Add constructor.
     * @param Action\Context $context
     * @param ProfileManager $profileManager
     * @param ProductSubscriptionProfileFactory $productProfileFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ObjectFactory $objectFactory
     */
    public function __construct(
        Action\Context $context,
        ProfileManager $profileManager,
        ProductSubscriptionProfileFactory $productProfileFactory,
        ProductRepositoryInterface $productRepository,
        ObjectFactory $objectFactory
    ) {
        parent::__construct($context);
        $this->profileManager = $profileManager;
        $this->productProfileFactory = $productProfileFactory;
        $this->productRepository = $productRepository;
        $this->objectFactory = $objectFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $profile = $this->profileManager->loadProfileFromRequest(SummaryInsertForm::FORM_DATA_KEY);
        if (null === $profile) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)
                ->setData(['error' => true, 'messages' => __('Profile Not Found')]);
        }

        $request = $this->getRequest()->getParams();
        $request['billing_frequency'] = $profile->getBillingFrequencyId();

        try {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productRepository->getById($request['product_id']);
        } catch (NoSuchEntityException $e) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)
                ->setData(['error' => true, 'messages' => __('Product Not Found')]);
        }

        $requestData = $this->objectFactory->create($request);

        try {
            $this->profileManager->addProduct($requestData, $product);

            $profile->setDataChanges(true);
            $this->profileManager->saveProfile();
        } catch (\Exception $e) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)
                ->setData(['error' => true, 'messages' => $e->getMessage()]);
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)
            ->setData(['error' => false, 'messages' => []]);
    }
}
