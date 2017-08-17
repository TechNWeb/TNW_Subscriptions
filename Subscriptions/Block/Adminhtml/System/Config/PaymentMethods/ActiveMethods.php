<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\System\Config\PaymentMethods;

use Magento\Config\Model\Config\Source\Yesno;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;

class ActiveMethods extends Field
{
    const SECTION_ID = 'tnw_subscriptions_payment_methods';
    const GROUP_ID = 'active_methods';

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * Yes/no model.
     *
     * @var Yesno
     */
    private $yesNo;

    /**
     * Template for active payment methods.
     *
     * @var string
     */
    private $activeMethodsTemplate = 'system/config/payment_methods/active_methods.phtml';

    /**
     * Payment codes which is available for subscription.
     *
     * @var array
     */
    private $availableMethodsCodes = [
        \Magento\OfflinePayments\Model\Checkmo::PAYMENT_METHOD_CHECKMO_CODE,
        \Magento\Paypal\Model\Config::METHOD_PAYFLOWPRO,
    ];

    /**
     * @param Context $context
     * @param PaymentHelper $paymentHelper
     * @param Yesno $yesNo
     * @param array $data
     */
    public function __construct(
        Context $context,
        PaymentHelper $paymentHelper,
        Yesno $yesNo,
        \TNW\Subscriptions\Model\Config $config,
        array $data = []
    ) {
        $this->yesNo = $yesNo;
        $this->paymentHelper = $paymentHelper;
        $config->getAvailablePaymentsList();

        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if (!$this->getTemplate()) {
            $this->setTemplate($this->activeMethodsTemplate);
        }

        return $this;
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->addData(
            [
                'payment_list' => $this->getActivePaymentMethodsList(),
                'source_model_values' => $this->yesNo->toOptionArray(),
                'group_id' => self::GROUP_ID,
            ]
        );

        return $this->_toHtml();
    }


    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = $this->_getElementHtml($element);

        return $this->_decorateRowHtml($element, $html);
    }

    /**
     * @return array
     */
    protected function getActivePaymentMethodsList()
    {
        /** @var array $result */
        $result = [];

        /** @var array $paymentMethods */
        $paymentMethods = $this->paymentHelper->getStoreMethods();

        foreach ($paymentMethods as $method) {
            if (in_array($method->getCode(), $this->availableMethodsCodes)) {
                $result[] = [
                    'title' => $method->getConfigData('title'),
                    'code' => $method->getCode(),
                    'config_name' => self::SECTION_ID . '/' . self::GROUP_ID . '/' . $method->getCode(),
                ];
            }
        }

        return $result;
    }
}
