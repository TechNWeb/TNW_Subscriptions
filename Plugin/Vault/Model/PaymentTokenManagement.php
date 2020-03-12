<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Vault\Model;

/**
 * Class PaymentTokenManagement
 * @package TNW\Subscriptions\Plugin\Vault\Model
 */
class PaymentTokenManagement
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    private $encryptor;

    public function __construct(
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $repository,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->encryptor = $encryptor;
        $this->paymentTokenRepository = $repository;
    }

    /**
     * @param \Magento\Vault\Model\PaymentTokenManagement $subject
     * @param $proceed
     * @param $token
     * @param $payment
     * @return mixed
     */
    public function aroundSaveTokenWithPaymentLink($subject, $proceed, $token, $payment)
    {
        $tokenDuplicate = $subject->getByGatewayToken(
            $token->getGatewayToken(),
            $token->getPaymentMethodCode(),
            $token->getCustomerId()
        );
        if ($token->getDetails() == "null" && $payment->getMethod() == "payflowpro") {
            $token->setDetails(json_encode([
                'cc_type' => $payment->getCcType(),
                'cc_exp_year' => $payment->getCcExpYear(),
                'cc_exp_month' => $payment->getCcExpYear(),
                'cc_last_4' => $payment->getCcLast4()
            ]));

        }

        if (!empty($tokenDuplicate)) {
            if ($token->getIsVisible() || $tokenDuplicate->getIsVisible()) {
                $token->setEntityId($tokenDuplicate->getEntityId());
                $token->setPublicHash($tokenDuplicate->getPublicHash());
                $token->setIsVisible(true);
            } elseif ($token->getIsVisible() === $tokenDuplicate->getIsVisible()) {
                $token->setEntityId($tokenDuplicate->getEntityId());
            } else {
                $token->setPublicHash(
                    $this->encryptor->getHash(
                        $token->getPublicHash() . $token->getGatewayToken()
                    )
                );
            }
            $this->paymentTokenRepository->save($token);
            $result = $subject->addLinkToOrderPayment($token->getEntityId(), $payment->getEntityId());
        } else {
            $result = $proceed($token, $payment);
        }

        return $result;
    }
}
