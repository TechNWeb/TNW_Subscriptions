<?php
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Edit\Product;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use Magento\Backend\App\Action;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Add extends Action
{
    /**
     * @var ProfileManager
     */
    private $profileManager;

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
     * @param ProductRepositoryInterface $productRepository
     * @param ObjectFactory $objectFactory
     */
    public function __construct(
        Action\Context $context,
        ProfileManager $profileManager,
        ProductRepositoryInterface $productRepository,
        ObjectFactory $objectFactory
    ) {
        parent::__construct($context);
        $this->profileManager = $profileManager;
        $this->productRepository = $productRepository;
        $this->objectFactory = $objectFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
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
