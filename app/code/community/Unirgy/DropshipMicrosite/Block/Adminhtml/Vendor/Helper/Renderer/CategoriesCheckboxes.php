<?php

class Unirgy_DropshipMicrosite_Block_Adminhtml_Vendor_Helper_Renderer_CategoriesCheckboxes extends Mage_Adminhtml_Block_Catalog_Category_Checkboxes_Tree implements Varien_Data_Form_Element_Renderer_Interface
{
    protected function _prepareLayout()
    {
        $this->setTemplate('udropship/vendor/helper/categories_checkboxes_tree.phtml');
    }

    protected $_element = null;

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }

    public function setElement(Varien_Data_Form_Element_Abstract $element)
    {
        $this->_element = $element;
        return $this;
    }

    public function getElement()
    {
        return $this->_element;
    }

    public function getLoadTreeUrl($expanded=null)
    {
        $params = array('_current'=>true, 'id'=>null,'store'=>null);
        if (
            (is_null($expanded) && Mage::getSingleton('admin/session')->getIsTreeWasExpanded())
            || $expanded == true) {
            $params['expand_all'] = true;
        }
        return $this->getUrl('adminhtml/umicrositeadmin_widget/categoriesJson', $params);
    }

}