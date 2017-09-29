<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\System\Config\PaymentMethods;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Paypal\Model\Config;
use TNW\Subscriptions\Model\Config as SubscriptionConfig;

/**
 * Class ActiveMethods
 */
class ActiveMethods extends Fieldset
{
    /**#@+
     * Config xml path constants
     */
    const SECTION_ID = 'tnw_subscriptions_payment_methods';
    const GROUP_ID = 'active_methods';
    /**#@-*/

    /**
     * Yes/no model.
     *
     * @var Yesno
     */
    private $yesNo;

    /**
     * Subscriptions config.
     *
     * @var SubscriptionConfig
     */
    private $config;

    /**
     * Payment codes which is available for subscription.
     *
     * @var array
     */
    private $availableMethodsCodes = [
        Checkmo::PAYMENT_METHOD_CHECKMO_CODE,
        Config::METHOD_PAYFLOWPRO,
        Config::METHOD_PAYMENT_PRO
    ];

    /**
     * ActiveMethods constructor.
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param Yesno $yesNo
     * @param SubscriptionConfig $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Yesno $yesNo,
        SubscriptionConfig $config,
        array $data = []
    ) {
        $this->yesNo = $yesNo;
        $this->config = $config;

        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        $this->generateFields($element);
        $html = $this->_getHeaderHtml($element);

        foreach ($element->getElements() as $field) {
            if ($field instanceof DataFieldset) {
                $html .= '<tr id="row_' . $field->getHtmlId() . '">
                             <td colspan="4">' . $field->toHtml() . '</td>
                             </tr>';
            } else {
                $html .= $field->toHtml();
            }
        }

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * Generates field for each payment method.
     *
     * @param $element AbstractElement
     */
    private function generateFields($element)
    {
        /** @var [] $configData */
        $configData = $this->getConfigData();

        /** @var [] $paymentMethods */
        $paymentMethods = $this->getActivePaymentMethodsList();

        foreach ($paymentMethods as $method) {
            $path = self::SECTION_ID . '/' . self::GROUP_ID . '/' . $method['code'];

            $inherit = true;
            $data = null;

            if (array_key_exists($path, $configData)) {
                $data = $configData[$path];
                $inherit = false;
            } else {
                $data = $this->getForm()->getConfigValue($path);
            }

            $element->addField(
                $method['code'],
                'select',
                $this->getFieldConfig($method, $data, $inherit)
            )->setRenderer(
                $this->getFieldRenderer()
            );
        }
    }

    /**
     * Get field renderer
     *
     * @return Field
     */
    private function getFieldRenderer()
    {
        if (empty($this->fieldRenderer)) {
            $this->fieldRenderer = $this->getLayout()->getBlockSingleton(
                Field::class
            );
        }

        return $this->fieldRenderer;
    }


    /**
     * Returns list of filtered active payment methods in magento.
     *
     * @return array
     */
    private function getActivePaymentMethodsList()
    {
        /** @var array $result */
        $result = [];
        /** @var array $paymentMethods */
        $paymentMethods = $this->config->getStoreConfig(PaymentHelper::XML_PATH_PAYMENT_METHODS);
        foreach ($paymentMethods as $code => $data) {
            $active = isset($data['active']) ? (bool)($data['active']) : false;
            if (in_array($code, $this->availableMethodsCodes) && $active) {
                if (in_array($code, [Config::METHOD_PAYFLOWPRO, Config::METHOD_PAYMENT_PRO])) {
                    $title = $this->config->getTitleForPaypal();
                } else {
                    $title = isset($data['title']) ? $data['title'] : '';
                }
                $result[] = [
                    'title' => $title,
                    'code' => $code
                ];
            }
        }

        return $result;
    }

    /**
     * Returns field config.
     *
     * @param $method
     * @param $data
     * @param $inherit
     * @return array
     */
    private function getFieldConfig($method, $data, $inherit)
    {
        return [
            'name' => 'groups[' . self::GROUP_ID . '][fields]['
                . $method['code'] . '][value]',
            'label' => $method['title'],
            'comment' => '',
            'value' => $data,
            'values' => $this->yesNo->toOptionArray(),
            'inherit' => $inherit,
            'scope' => $this->getForm()->getScope(),
            'scope_id' => $this->getForm()->getScopeId(),
            'scope_label' => __('[WEBSITE]'),
            'can_use_default_value' => $this->getForm()
                ->canUseDefaultValue(1),
            'can_use_website_value' => $this->getForm()
                ->canUseWebsiteValue(1)
        ];
    }
}
