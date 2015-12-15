<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Grid_Renderer_Description extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Properly truncates the description
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $storeId = $this->getRequest()->getParam('store', 0);
        $helper = Mage::helper('bronto_product');
        $limit = $helper->getCharLimit('store', $storeId);
        $attr = $helper->getDescriptionAttr('store', $storeId);
        return $helper->truncateText($row->getData($attr), $limit);
    }
}
