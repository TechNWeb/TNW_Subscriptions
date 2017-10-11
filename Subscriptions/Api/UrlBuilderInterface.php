<?php
/**
 * Created by PhpStorm.
 * User: serhii
 * Date: 10.10.17
 * Time: 17:52
 */
namespace TNW\Subscriptions\Api;


/**
 * Class to retrieve subscription url
 */
interface UrlBuilderInterface
{
    /**
     * Get subscription edit URL
     *
     * @param $id
     * @return string
     */
    public function getEditUrl($id);

    /**
     * Get subscription edit URL link
     *
     * @param $id
     * @param bool $targetBlank
     * @return string
     */
    public function getEditHtmlLink($id, $targetBlank = true);
}