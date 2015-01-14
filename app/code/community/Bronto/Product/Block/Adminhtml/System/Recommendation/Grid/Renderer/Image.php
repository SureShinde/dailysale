<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Grid_Renderer_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Returns the image as it would appear in the email
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $product = Mage::getModel('catalog/product')->load($row->getId());
        $imageUrl = Mage::helper('bronto_common')->getProductImageUrl($product);
        return '<img src="' . $imageUrl . '" alt="' . (empty($imageUrl)  ? 'Not Found' : $imageUrl) . '" />';
    }
}
