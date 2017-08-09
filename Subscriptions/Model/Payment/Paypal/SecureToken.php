<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Payment\Paypal;

use Magento\Quote\Model\Quote;
use Magento\Framework\Math\Random;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\Paypal\Model\Payflow\Transparent;
use Magento\Paypal\Model\Payflowpro;

/**
 * Class SecureToken
 */
class SecureToken
{
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @var Transparent
     */
    private $transparent;

    /**
     * @param UrlInterface $url
     * @param Random $mathRandom
     * @param Transparent $transparent
     */
    public function __construct(
        UrlInterface $url,
        Random $mathRandom,
        Transparent $transparent
    ) {
        $this->url = $url;
        $this->mathRandom = $mathRandom;
        $this->transparent = $transparent;
    }

    /**
     * Gets the Secure Token from Paypal for TR.
     *
     * @param Quote $quote
     *
     * @return DataObject
     * @throws \Exception
     */
    public function requestToken(Quote $quote)
    {
        $request = $this->transparent->buildBasicRequest();

        $request->setTrxtype(Payflowpro::TRXTYPE_AUTH_ONLY);
        $request->setVerbosity('HIGH');
        $request->setAmt(0);
        $request->setCreatesecuretoken('Y');
        $request->setSecuretokenid($this->mathRandom->getUniqueHash());
        $request->setReturnurl($this->url->getUrl('tnw_subscriptions/subscriptionprofile_create_paypal/response'));
        $request->setErrorurl($this->url->getUrl('tnw_subscriptions/subscriptionprofile_create_paypal/response'));
        //TODO WTF? Where is this controller?
        //$request->setCancelurl($this->url->getUrl('paypal/transparent/cancel'));
        $request->setDisablereceipt('TRUE');
        $request->setSilenttran('TRUE');

        $this->transparent->fillCustomerContacts($quote, $request);

        $result = $this->transparent->postRequest($request, $this->transparent->getConfig());

        return $result;
    }
}