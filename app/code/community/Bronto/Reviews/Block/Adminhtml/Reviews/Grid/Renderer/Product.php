<?php

class Bronto_Reviews_Block_Adminhtml_Reviews_Grid_Renderer_Product extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Return the product named URL
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        if ($row->getProductId()) {
            $url = Mage::getModel('adminhtml/url')->getUrl('*/catalog_product/edit', array('id' => $row->getProductId()));
            return sprintf('<a href="%s">%s</a>', $url, $row->getProductName());
        }
        return 'NA';
    }
}
