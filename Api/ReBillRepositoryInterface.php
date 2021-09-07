<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\ReBill\CollectionFactory;
use TNW\Subscriptions\Api\Data\ReBillInterface as Model;

/**
 * Interface ReBillRepositoryInterface - used to describe the re-bill repository interface
 */
interface ReBillRepositoryInterface
{
    /**
     * @param Model $reBill
     * @return mixed
     */
    public function save(Model $reBill);

    /**
     * @param $reBillId
     * @return mixed
     */
    public function getById($reBillId);

    /**
     * @param $reBillToken
     * @return mixed
     */
    public function getByToken($reBillToken);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param Model $reBill
     * @return mixed
     */
    public function delete(Model $reBill);

    /**
     * @param $id
     * @return mixed
     */
    public function deleteById($id);

    /**
     * @param $token
     * @return mixed
     */
    public function deleteByToken($token);
}
