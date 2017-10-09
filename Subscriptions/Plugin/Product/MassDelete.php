<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Plugin\Product;


use Magento\Catalog\Controller\Adminhtml\Product\MassDelete as ControllerMassDelete;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Message\Manager;

class MassDelete
{
    /**
     * Controller result
     *
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * MassDelete constructor.
     *
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        ResultFactory $resultFactory
    ) {

        $this->resultFactory = $resultFactory;
    }


    /**
     * Add error message if mass delete product have error
     *
     * @param ControllerMassDelete $massDelete
     * @param \Closure $proceed
     * @return mixed|null
     */
    public function aroundExecute(ControllerMassDelete $massDelete, \Closure $proceed)
    {
        try {
            // call the core observed function
            $returnValue = $proceed();
        } catch (\Exception $e) {
            $messageManager = ObjectManager::getInstance()->get(Manager::class);
            $messageManager->addErrorMessage($e->getMessage());
            $returnValue = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('catalog/*/index');
        }

        return $returnValue;
    }
}