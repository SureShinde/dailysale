<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Grid_Renderer_Price extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Properly render the price
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $storeId = $this->getRequest()->getParam('store', 0);
        list($base, $currency, $options) = Mage::helper('bronto_product')->currencyAndOptions($storeId);
        $price = $row->getPrice();
        if ($base != $currency) {
            $price = $base->convert($price, $currency, $options);
        }
        return $currency->formatTxt($price, $options);
    }
}
