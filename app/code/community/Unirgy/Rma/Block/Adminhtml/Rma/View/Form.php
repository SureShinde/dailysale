<?php

class Unirgy_Rma_Block_Adminhtml_Rma_View_Form extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('urma/rma/view/form.phtml');
    }
}