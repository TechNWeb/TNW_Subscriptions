<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Plugin\Product;

use Magento\Catalog\Controller\Adminhtml\Product\MassDelete as ControllerMassDelete;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\Manager;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * Plugin for mass delete product
 *
 * Class MassDelete
 * @package TNW\Subscriptions\Plugin\Product
 */
class MassDelete
{
    /**
     * Controller result
     *
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     *
     *
     * @var Manager
     */
    private $manager;

    /**
     * @param ResultFactory $resultFactory
     * @param Manager $messageManager
     */
    public function __construct(
        ResultFactory $resultFactory,
        Manager $messageManager
    ) {
        $this->resultFactory = $resultFactory;
        $this->manager = $messageManager;
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
            $this->manager->addErrorMessage($e->getMessage());
            $returnValue = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('catalog/*/index');
        }

        return $returnValue;
    }
}
