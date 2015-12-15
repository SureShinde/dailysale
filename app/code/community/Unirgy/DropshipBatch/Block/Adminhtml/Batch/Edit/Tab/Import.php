<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_Dropship
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */

class Unirgy_DropshipBatch_Block_Adminhtml_Batch_Edit_Tab_Import extends Unirgy_Dropship_Block_Adminhtml_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setDestElementId('batch_form');
    }

    protected function _prepareForm()
    {
        $batch = Mage::registry('batch_data');
        $hlp = Mage::helper('udbatch');
        $id = $this->getRequest()->getParam('id');
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $batchType = $this->getRequest()->getParam('type');

        $fieldset = $form->addFieldset('batch_form', array('legend'=>Mage::helper('udropship')->__('Batch Info')));
        $this->_addElementTypes($fieldset);

        if (Mage::getStoreConfigFlag('udropship/batch/allow_all_vendors_import')) {
            $vendorField = 'vendors_import_orders';
        } else {
            $vendorField = 'udropship_vendor';
        }

        $fieldset->addField('vendor_id', $vendorField, array(
            'name'      => 'vendor_id',
            'label'     => Mage::helper('udropship')->__('Vendor'),
            'class'     => 'required-entry',
            'required'  => true,
        ));

        $fieldset->addField('batch_type', 'hidden', array(
            'name'      => 'batch_type',
            'value'     => $batchType,
        ));
        $fieldset->addField('use_custom_template', 'select', array(
            'name'      => 'use_custom_template',
            'label'     => Mage::helper('udropship')->__('Use Template'),
            'options'   => Mage::getSingleton('udbatch/source')->setPath('use_custom_template')->toOptionHash(),
        ));

        $fieldset = $form->addFieldset('default_form', array('legend'=>Mage::helper('udropship')->__("Import from vendor's default locations")));

        $fieldset->addField("{$batchType}_default", 'select', array(
            'name'      => "{$batchType}_default",
            'label'     => Mage::helper('udropship')->__('Default locations'),
            'options'   => Mage::getSingleton('udropship/source')->setPath('yesno')->toOptionHash(),
        ));

        $fieldset = $form->addFieldset('upload_form', array('legend'=>Mage::helper('udropship')->__('Import from uploaded file')));

        $fieldset->addField("{$batchType}_upload", 'file', array(
            'name'      => "{$batchType}_upload",
            'label'     => Mage::helper('udropship')->__('Upload file'),
        ));

        $fieldset = $form->addFieldset('textarea_form', array('legend'=>Mage::helper('udropship')->__('Import from pasted content')));

        $fieldset->addField("{$batchType}_textarea", 'textarea', array(
            'name'      => "{$batchType}_textarea",
            'label'     => Mage::helper('udropship')->__('Paste content'),
        ));

        $fieldset = $form->addFieldset('locations_form', array('legend'=>Mage::helper('udropship')->__('Import from custom locations')));

        $fieldset->addField("{$batchType}_locations", 'textarea', array(
            'name'      => "{$batchType}_locations",
            'label'     => Mage::helper('udropship')->__('Custom locations'),
            'note'      => Mage::helper('udropship')->__('Use <a href="http://unirgy.com/wiki/udropship/batch/reference" target="udbatch_reference">reference</a> for location format, separate multiple locations with new line'),
        ));

        if ($batch) {
            $form->setValues($batch->getData());
        }

        return parent::_prepareForm();
    }

    protected function _getAdditionalElementTypes()
    {
        return array_merge(parent::_getAdditionalElementTypes(), array(
            'vendors_import_orders'=>Mage::getConfig()->getBlockClassName('udbatch/vendor_htmlselect')
        ));
    }

}