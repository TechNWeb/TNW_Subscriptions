<?php
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResponseInterface;

/**
 * Additional save action
 */
class AdditionalSave extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var \TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * AdditionalSave constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
    ) {
        parent::__construct($context);
        $this->layoutFactory = $layoutFactory;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        try {
            $attributesData = $this->getRequest()->getParams();

            /** @var \TNW\Subscriptions\Model\SubscriptionProfile $profile */
            $profile = $this->subscriptionProfileRepository->getById($attributesData['entity_id']);
            foreach ($attributesData as $attributeCode => $value) {
                /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
                $attribute = $this->_objectManager->get(\Magento\Eav\Model\Config::class)
                    ->getAttribute(\TNW\Subscriptions\Model\SubscriptionProfile::ENTITY, $attributeCode);

                if (!$attribute->getAttributeId()) {
                    continue;
                }

                $profile->setData($attributeCode, $value);
                $attribute->getBackend()->validate($profile);
            }

            $this->subscriptionProfileRepository->save($profile);
            $this->messageManager->addSuccessMessage(__('You saved the profile attribute.'));
        } catch (\Magento\Eav\Model\Entity\Attribute\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while updating the profile(s) attributes.')
            );
        }

        $layout = $this->layoutFactory->create();
        $layout->initMessages();

        $response['error'] = true;
        $response['messages'] = [$layout->getMessagesBlock()->getGroupedHtml()];

        return $this->resultFactory
            ->create(ResultFactory::TYPE_JSON)
            ->setData($response);
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_Subscriptions::SubscriptionProfile_edit'
        );
    }
}
