<?php

class Unirgy_DropshipBatch_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->_forward('index', 'vendor');
    }
    public function vendorAutocompleteAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('udbatch/vendor_autocomplete')
                ->setVendorPrefix($this->getRequest()->getParam('vendor_name'))
                ->toHtml()
        );
    }
}