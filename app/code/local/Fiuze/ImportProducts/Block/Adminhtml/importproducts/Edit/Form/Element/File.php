<?php
/**
 * Custom file form element that generates correct xml file URL
 *
 * @author Mihail
 */
class Fiuze_ImportProducts_Block_Adminhtml_ImportProducts_Edit_Form_Element_File extends Varien_Data_Form_Element_File
{
    /**
     * Get xml file url
     *
     * @return string
     */
    protected function _getUrl()
    {
        $url = false;
        if ($this->getValue()) {
            $url = Mage::helper('fiuze_importproducts/file')->getBaseUrl() . '/' . $this->getValue();
        }
        return $url;
    }
}