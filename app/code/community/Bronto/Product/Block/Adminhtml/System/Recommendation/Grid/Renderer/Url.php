<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Grid_Renderer_Url extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * Returns the url as a clickable link to the product
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $storeId = $this->getRequest()->getParam('store', 0);
        $url = Mage::helper('bronto_common/product')->getProductAttribute($row->getId(), 'url', $storeId);
        return '<a href="' . $url . '">' . $url . '</a>';
    }
}
