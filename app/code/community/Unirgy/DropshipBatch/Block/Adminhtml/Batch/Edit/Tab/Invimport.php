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

class Unirgy_DropshipBatch_Block_Adminhtml_Batch_Edit_Tab_Invimport extends Unirgy_Dropship_Block_Adminhtml_Widget_Form
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

        $fieldset = $form->addFieldset('batch_form', array('legend'=>$hlp->__('Batch Info')));
        $this->_addElementTypes($fieldset);

        $fieldset->addField('vendor_id', 'udropship_vendor', array(
            'name'      => 'vendor_id',
            'label'     => $hlp->__('Vendor'),
            'class'     => 'required-entry',
            'required'  => true,
        ));

        $fieldset->addField('batch_type', 'hidden', array(
            'name'      => 'batch_type',
            'value'     => 'import_inventory',
        ));
        $fieldset->addField('batch_notes', 'textarea', array(
            'name'      => 'batch_notes',
            'label'     => $hlp->__('Batch notes'),
        ));
        $fieldset->addField('use_custom_template', 'select', array(
            'name'      => 'use_custom_template',
            'label'     => $hlp->__('Use Template'),
            'options'   => Mage::getSingleton('udbatch/source')->setPath('use_custom_template')->toOptionHash(),
        ));

        $fieldset = $form->addFieldset('default_form', array('legend'=>$hlp->__("Import from vendor's default locations")));

        $fieldset->addField('import_inventory_default', 'select', array(
            'name'      => 'import_inventory_default',
            'label'     => $hlp->__('Default locations'),
            'options'   => Mage::getSingleton('udropship/source')->setPath('yesno')->toOptionHash(),
        ));

        $fieldset = $form->addFieldset('upload_form', array('legend'=>$hlp->__('Import from uploaded file')));

        $fieldset->addField('import_inventory_upload', 'file', array(
            'name'      => 'import_inventory_upload',
            'label'     => $hlp->__('Upload file'),
        ));

        $fieldset = $form->addFieldset('textarea_form', array('legend'=>$hlp->__('Import from pasted content')));

        $fieldset->addField('import_inventory_textarea', 'textarea', array(
            'name'      => 'import_inventory_textarea',
            'label'     => $hlp->__('Paste content'),
        ));

        $fieldset = $form->addFieldset('locations_form', array('legend'=>$hlp->__('Import from custom locations')));

        $fieldset->addField('import_inventory_locations', 'textarea', array(
            'name'      => 'import_inventory_locations',
            'label'     => $hlp->__('Custom locations'),
            'note'      => $hlp->__('Use <a href="http://unirgy.com/wiki/udropship/batch/reference" target="udbatch_reference">reference</a> for location format, separate multiple locations with new line'),
        ));

        if ($batch) {
            $form->setValues($batch->getData());
        }

        return parent::_prepareForm();
    }

}