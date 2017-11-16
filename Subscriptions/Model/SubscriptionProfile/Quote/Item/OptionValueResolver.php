<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Quote\Item;

/**
 * Value decoder.
 */
class OptionValueResolver
{
    /**
     * Data formats.
     */
    const JSON_FORMAT = 'json';
    const SERIALISED_FORMAT = 'serialized';

    /**
     * Decodes value from json/serialised format.
     *
     * @param string $value
     * @return mixed
     */
    public static function decode($value)
    {
        if (self::isJson($value)) {
            $result = \Zend_Json::decode($value);
        } else {
            $result = unserialize($value);
        }

        return $result;
    }

    /**
     * Returns value format.
     *
     * @param string $value
     * @return string
     */
    public static function getValueFormat($value)
    {
        return self::isJson($value) ? self::JSON_FORMAT : self::SERIALISED_FORMAT;
    }

    /**
     * Returns encoded value.
     *
     * @param mixed $value
     * @param string $format
     * @return string
     */
    public static function getEncodedValue($value, $format = self::SERIALISED_FORMAT)
    {
        if ($format === self::JSON_FORMAT) {
            $result = \Zend_Json::encode($value);
        } else {
            $result = serialize($value);
        }

        return $result;
    }

    /**
     * Checks if value is in json format.
     *
     * @param mixed $value
     * @return bool
     */
    private static function isJson($value)
    {
        if ($value === '') {
            return false;
        }

        \json_decode($value);
        if (\json_last_error()) {
            return false;
        }

        return true;
    }
}