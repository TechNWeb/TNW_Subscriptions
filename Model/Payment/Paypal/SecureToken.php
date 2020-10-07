<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

use Magento\Framework\Math\Random;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

/**
 * Class SecureToken - secure token model for paypal
 */
class SecureToken
{
    /**
     * Auth only transaction code
     */
    const TRXTYPE_AUTH_ONLY = 'A';

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @var mixed
     */
    private $transparent;

    /**
     * SecureToken constructor.
     * @param UrlInterface $url
     * @param Random $mathRandom
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        UrlInterface $url,
        Random $mathRandom,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        $this->url = $url;
        $this->mathRandom = $mathRandom;
        if ($moduleManager->isEnabled("Magento_Paypal")) {
            $this->transparent = $objectManager->get(\Magento\Paypal\Model\Payflow\Transparent::class);
        }
    }

    /**
     * Gets the Secure Token from Paypal for TR.
     *
     * @param DataObject $object
     *
     * @return DataObject
     * @throws \Exception
     */
    public function requestToken(DataObject $object)
    {
        $request = $this->transparent->buildBasicRequest();

        $request->setTrxtype(self::TRXTYPE_AUTH_ONLY);
        $request->setVerbosity('HIGH');
        $request->setAmt(0);
        $request->setCreatesecuretoken('Y');
        $request->setSecuretokenid($this->mathRandom->getUniqueHash());
        $routePath = 'tnw_subscriptions/paypal/response';
        $url = ($object instanceof SubscriptionProfileInterface)
            ? $this->url->getUrl(
                $routePath,
                [
                    SummaryInsertForm::FORM_DATA_KEY => $object->getId()
                ]
            )
            : $this->url->getUrl($routePath);
        $request->setReturnurl($url);
        $request->setErrorurl($url);
        $request->setDisablereceipt('TRUE');
        $request->setSilenttran('TRUE');

        $this->transparent->fillCustomerContacts($object, $request);

        $result = $this->transparent->postRequest($request, $this->transparent->getConfig());

        return $result;
    }
}
