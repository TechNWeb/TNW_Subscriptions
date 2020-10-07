<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api;

/**
 * Interface UrlBuilderInterface - determines the functionality to implement for
 * url builder
 */
interface UrlBuilderInterface
{
    /**
     * Get subscription edit URL
     *
     * @param int $id
     * @return string
     */
    public function getEditUrl($id);

    /**
     * Get subscription edit URL link
     *
     * @param int $id
     * @param bool $targetBlank
     * @return string
     */
    public function getEditHtmlLink($id, $targetBlank = true);
}
