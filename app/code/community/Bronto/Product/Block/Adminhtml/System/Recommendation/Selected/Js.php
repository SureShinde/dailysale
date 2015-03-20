<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Selected_Js extends Mage_Adminhtml_Block_Abstract
{
    /**
     * Override for template path
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('bronto/product/recommendation/selected.js.phtml');
    }

    /**
     * Url for the ajax picker
     *
     * @return string
     */
    public function getSelectAjaxUrl()
    {
        return $this->getUrl('*/*/selected', array('_current' => true));
    }
}
