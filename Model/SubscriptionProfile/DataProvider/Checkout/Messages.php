<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Checkout;

use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Checkout messages data provider.
 */
class Messages extends AbstractDataProvider
{
    /**
     * @var QuoteSessionInterface
     */
    private $quoteSession;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param QuoteSessionInterface $quoteSession
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        QuoteSessionInterface $quoteSession,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->quoteSession = $quoteSession;
    }

    /**
     * Get messages from quote session and convert them to component data.
     * @return array
     */
    public function getData()
    {
        list($messages, $needReload) = $this->retrieveQuoteSessionMessages($this->quoteSession->getErrors(true));
        $data = [
            'messages' => [
                'error' => $messages,
                'needReload' => $needReload,
            ],
        ];

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getConfigData()
    {
        return [];
    }

    /**
     * Retrieve messages from quote session.
     *
     * @param array|null $errors
     * @return array
     */
    private function retrieveQuoteSessionMessages($errors)
    {
        $messages = [];
        $needReload = false;

        if (is_array($errors) && !empty($errors)) {
            foreach ($errors as $error) {
                if (!empty($error)) {
                    if (is_array($error)) {
                        // $error may be array. Then the first element is error message itself.
                        $messages[] = reset($error);
                        if (isset($error['needReload'])) {
                            $needReload = $needReload || ($error['needReload'] ? true : false);
                        }
                    } else {
                        $messages[] = $error;
                    }
                }
            }
        }

        return [$messages, $needReload];
    }
}
