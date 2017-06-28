<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Data\Form\Element;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\Http;

/**
 * New form element for addon text field representation.
 * It inherit from Text element and add extra possibilities
 */
class AddonText extends \Magento\Framework\Data\Form\Element\Text
{
    /**
     * Get addon text
     *
     * @return mixed
     */
    public function getAddonText()
    {
        return $this->getData('addon_text');
    }

    /**
     * Set addon text
     *
     * @param $value
     * @return void
     */
    public function setAddonText($value)
    {
        $this->setData('addon_text', $value);
    }

    public function getElementHtml()
    {
        $html = '';
        $htmlId = $this->getHtmlId();

        $beforeElementHtml = $this->getBeforeElementHtml();
        if ($beforeElementHtml) {
            $html .= '<label class="addbefore" for="' . $htmlId . '">' . $beforeElementHtml . '</label>';
        }

        $html .= '<div  class="admin__field-control"><div class="admin__control-addon">';
        $html .= '<input id="' . $htmlId . '" name="' . $this->getName() . '" ' . $this->_getUiId() . ' value="' .
            $this->getEscapedValue() . '" ' . $this->serialize($this->getHtmlAttributes()) . '/>';
        $html .=
            '<label for="' . $htmlId . '" class="admin__addon-prefix"><span>'
            . $this->getAddonText()
            . '</span></label>';
        $html .= '</div></div>';
        // Error container
        $html .= '<label style="display: none;" id="' . $htmlId . '-error" class="mage-error" generated="true" for="' . $htmlId . '"></label>';

        $afterElementJs = $this->getAfterElementJs();
        if ($afterElementJs) {
            $html .= $afterElementJs;
        }

        $afterElementHtml = $this->getAfterElementHtml();
        if ($afterElementHtml) {
            $html .= '<label class="addafter" for="' . $htmlId . '">' . $afterElementHtml . '</label>';
        }

        return $html;
    }
}
